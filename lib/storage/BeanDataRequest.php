<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/ErrorResponse.php");
include_once("storage/ImageDataResponse.php");
include_once("storage/FileDataResponse.php");
include_once("storage/StorageVersion.php");

/**
 * Parses a storage request and hands it to the matching BeanDataResponse.
 *
 * Request forms:
 *  query: storage.php?cmd=<cmd>&class=<class>&id=<id>[&width=..&height=..][&v=<version>]
 *  slug : <route> = <cmd>/<class>/<id>/[<response route parameters>/][<version>/]<name>.<id>[.<ext>]
 *
 * The route grammar is owned here: cmd/class/id, then the parameters the response class declares
 * with RouteParameters() (width/height for images), then the optional version and the slug name.
 */
class BeanDataRequest
{
    const string KEY_CMD = "cmd";
    const string KEY_ID = "id";
    const string KEY_CLASS = "class";

    const string CMD_DATA = "data";
    const string CMD_PHOTO = "image";

    const string KEY_ROUTE = "route";

    /**
     * Response class for each command
     */
    const array RESPONSES = array(
        BeanDataRequest::CMD_DATA => FileDataResponse::class,
        BeanDataRequest::CMD_PHOTO => ImageDataResponse::class,
    );

    protected string $cmd = "";

    /**
     * Requested version token - from query parameter 'v' or from the slug route
     * @var string
     */
    protected string $version = "";

    /**
     * True if the version token came from the slug route (path segment) - false if from the query
     * @var bool
     */
    protected bool $versionInPath = false;

    /**
     * Slug name segment without extension - '<name>.<id>'
     * @var string
     */
    protected string $slugName = "";

    public static function ConsumeRoute(bool $required, string ...$parameters) : void
    {
        if (!isset($_GET[BeanDataRequest::KEY_ROUTE])) return;

        $parts = Spark::Split($_GET[BeanDataRequest::KEY_ROUTE]);

        foreach ($parameters as $param) {
            if (!empty($parts)) {
                $_GET[$param] = array_shift($parts);
            }
            else {
                if ($required) throw new Exception("Parameter[$param] not found in route");
            }
        }

        $_GET[BeanDataRequest::KEY_ROUTE] = implode('/', $parts);
    }

    public function __construct()
    {
        //disable gzip, stop other PHP cache headers (.htaccess to stop mod_expires)
        ini_set("zlib.output_compression", 0);
        ini_set('session.cache_limiter', ''); //stop php cache headers
        session_cache_limiter("");

        try {

            BeanDataRequest::ConsumeRoute(true, BeanDataRequest::KEY_CMD, BeanDataRequest::KEY_CLASS, BeanDataRequest::KEY_ID);

            if (!isset($_GET[BeanDataRequest::KEY_CMD])) {
                throw new Exception("Missing storage access command");
            }
            $this->cmd = (string)$_GET[BeanDataRequest::KEY_CMD];

            if (!isset(BeanDataRequest::RESPONSES[$this->cmd])) {
                throw new Exception("Unrecognized storage access command");
            }

            if (!isset($_GET[BeanDataRequest::KEY_ID])) {
                throw new Exception("Missing id key");
            }
            $id = (int)$_GET[BeanDataRequest::KEY_ID];

            if (!isset($_GET[BeanDataRequest::KEY_CLASS])) {
                throw new Exception("Missing class key");
            }
            $className = (string)$_GET[BeanDataRequest::KEY_CLASS];

            $responseClass = BeanDataRequest::RESPONSES[$this->cmd];

            //response specific route parameters - width/height for images
            BeanDataRequest::ConsumeRoute(false, ...$responseClass::RouteParameters());

            //[version/]name
            $this->consumeRouteTail();

            $resp = new $responseClass($id, $className);
            if (!($resp instanceof BeanDataResponse)) {
                throw new Exception("Unable to construct BeanDataResponse");
            }
            $resp->setRequest($this);

            $resp->send();

        }
        catch (Exception $e) {
            Debug::ErrorLog("Exception processing this request: ".$e->getTraceAsString());
            $resp = new ErrorResponse();
            $resp->sendException($e);
        }

    }

    /**
     * Remaining route after cmd/class/id and the response route parameters is [version, name] or [name].
     * Query parameter 'v' takes precedence over the route version.
     * @return void
     */
    protected function consumeRouteTail() : void
    {
        if (isset($_GET[StorageVersion::KEY]) && StorageVersion::ValidToken((string)$_GET[StorageVersion::KEY])) {
            $this->version = (string)$_GET[StorageVersion::KEY];
        }

        if (!isset($_GET[BeanDataRequest::KEY_ROUTE])) return;

        $parts = Spark::Split((string)$_GET[BeanDataRequest::KEY_ROUTE]);
        if (count($parts) < 1) return;

        if (count($parts) >= 2 && StorageVersion::ValidToken((string)$parts[0])) {
            $token = (string)array_shift($parts);
            if ($this->version === "") {
                $this->version = $token;
                $this->versionInPath = true;
            }
        }

        //name: drop a non numeric extension - keep '.<id>', header safe characters only (slug names are [a-z0-9-])
        $name = (string)end($parts);
        $name = preg_replace('/\.[A-Za-z][A-Za-z0-9]{0,4}$/', '', $name);
        $this->slugName = preg_replace('/[^A-Za-z0-9._-]/', '', $name);

        $_GET[BeanDataRequest::KEY_ROUTE] = "";
    }

    /**
     * Requested version token or empty string
     * @return string
     */
    public function getVersion() : string
    {
        return $this->version;
    }

    /**
     * Slug name '<name>.<id>' (without extension) or empty string for non slug requests
     * @return string
     */
    public function getSlugName() : string
    {
        return $this->slugName;
    }

    /**
     * The URL of this request with the version token replaced by '$token'.
     * Slug requests: the path segment before the name. Query requests: the 'v' parameter.
     * Always a local absolute path.
     * @param string $token
     * @return string Empty string if this request has no version token
     */
    public function versionURL(string $token) : string
    {
        if ($this->version === "") return "";

        $uri = (string)($_SERVER["REQUEST_URI"] ?? "");
        if ($uri === "") return "";

        $pos = strpos($uri, "?");
        $path = ($pos === false) ? $uri : substr($uri, 0, $pos);
        $query = ($pos === false) ? "" : substr($uri, $pos + 1);

        //always a local absolute path - '//host/...' would be a protocol relative URL
        $path = "/" . ltrim($path, "/");

        if ($this->versionInPath) {
            //.../<version>/<name>
            $segments = explode("/", $path);
            $index = count($segments) - 2;
            if ($index < 1 || $segments[$index] !== $this->version) return "";
            $segments[$index] = $token;
            $path = implode("/", $segments);
        }
        else {
            $params = array();
            parse_str($query, $params);
            if ((string)($params[StorageVersion::KEY] ?? "") !== $this->version) return "";
            $params[StorageVersion::KEY] = $token;
            $query = http_build_query($params, "", "&", PHP_QUERY_RFC3986);
        }

        return $path . ($query !== "" ? "?" . $query : "");
    }

}
