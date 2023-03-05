<?php
include_once("storage/HTTPResponse.php");
include_once("storage/CacheFile.php");
include_once("storage/FileStorageObject.php");
include_once("auth/Authenticator.php");

abstract class BeanDataResponse extends HTTPResponse
{

    protected $className = "";
    protected $id = -1;
    protected $field = "";
    protected $row = array();

    /**
     * @var DBTableBean
     */
    protected $bean = NULL;

    protected $etag_parts = array();
    protected $disposition = "inline";

    public $skip_cache = FALSE;

    /**
     * This flag is set when request URI contains specific field name to return from the result row.
     * Custom key name from the result row is allowed only if contents are of type StorageObject
     * To limit arbitrary data access
     * @var bool
     */
    protected $field_requested = FALSE;

    public function __construct(int $id, string $className)
    {
        parent::__construct();

        $this->id = $id;
        $this->className = $className;

        $globals = SparkGlobals::Instance();
        $globals->includeBeanClass($className);

        $this->bean = new $this->className();

        $this->etag_parts[] = $this->className;
        $this->etag_parts[] = $this->id;
        $this->etag_parts[] = get_class($this);

        if (isset($_GET["field"])) {
            $this->field = $_GET["field"];
            $this->field_requested = TRUE;
        }

        //sub-classes set $this->field
        if (!$this->bean->haveColumn($this->field)) {
            throw new Exception("Bean does not support this field");
        }

        $this->etag_parts[] = $this->field;

    }

    protected function authorizeAccess()
    {
        if (!$this->bean->haveColumn("auth_context")) {
            debug("No auth_context defined");
            return;
        }

        $qry = $this->bean->queryField($this->bean->key(), $this->id, 1);

        $storageTypes = $this->bean->columns();

        foreach ($storageTypes as $name => $storageType) {
            if (strpos($storageType, "blob") !== FALSE) continue;

            $qry->select->fields()->set($name);
        }

        if (!$qry->exec()) throw new Exception("Unable to query for auth_context");

        $row = $qry->next();

        $auth_context = $row["auth_context"];

        if (strlen($auth_context) < 1) return;

        debug("Protected resource requested - auth_context: $auth_context");

        Session::Start();

        Authenticator::AuthorizeResource($auth_context, $row, TRUE);

        Session::Close();
    }

    protected function loadBeanData()
    {
        if ($this->id == -1) {

            $funcname = "Default_" . $this->className;

            if (is_callable($funcname)) {
                $funcname($this->row);
            }
            else throw new Exception("No default value for this class");

        }
        else {
            debug("Fetching ID: " . $this->id . " Bean: " . get_class($this->bean));
            $this->row = $this->bean->getByID($this->id);
            debug("Data: ", array_keys($this->row));

        }

        if (!isset($this->row[$this->field])) {
            throw new Exception("No data for this blob field");
        }
    }

    protected function unpackStorageObject()
    {
        debug("...");

        $object = @unserialize($this->row[$this->field]);

        if ($object instanceof StorageObject) {

            debug("Unpacked: " . get_class($object));

            //replace result with storageobject data?
            //$this->row = array();
            //image resizer expects row["photo"]
            $object->setDataKey($this->field);
            $object->deconstruct($this->row, FALSE);

        }
        else {

            debug("Field[$this->field] does not contain StorageObject");

            //Limit arbitrary data access from the result row
            if ($this->field_requested) {
                throw new Exception("Field does not contain StorageObject");
            }
            //continue for objects transacted to db as dbrow and the default key name will be used (photo or data)

        }
    }

    /**
     * All content will be hashed and used as ETag header field
     * @return array
     */
    protected function getETagParts(): array
    {
        return $this->etag_parts;
    }



    protected function fillHeaders()
    {
        // set headers and etag
        $last_modified = time();

        //use timestamp from storage object
        if (isset($this->row["timestamp"]) && $this->row["timestamp"]) {
            $last_modified = strtotime($this->row["timestamp"]);
            debug("Using last-modified from [timestamp]: ".$last_modified);
        }
        else if (isset($this->row["date_upload"])) {
            $last_modified = strtotime($this->row["date_upload"]);
            debug("Using last-modified from [date_upload]: ".$last_modified);
        }
        else if (isset($this->row["date_updated"])) {
            $last_modified = strtotime($this->row["date_updated"]);
            debug("Using last-modified from [date_updated]: ".$last_modified);
        }
        else {
            debug("Using last-modified from current date/time: ".$last_modified);
        }

        $modified = gmdate(HTTPResponse::DATE_FORMAT, strtotime($last_modified));
        debug("Last-Modified: $modified");
        //always keep one year ahead from request time
        $expires = gmdate(HTTPResponse::DATE_FORMAT, strtotime("+1 year", $last_modified));
        debug("Expires: $expires");

        $etag = md5(implode("|", $this->getETagParts()) . "-" . $last_modified);
        debug("ETag: $etag");

        $this->setHeader("ETag", $etag);

        $this->setHeader("Expires", $expires);

        $this->setHeader("Last-Modified", $modified);

        $mime = "application/octet-stream";

        if (isset($this->row["mime"])) {
            $mime = $this->row["mime"];
        }

        $this->setHeader("Content-Type", $mime);

        $this->setHeader("Cache-Control", "no-cache, must-revalidate");

        //$this->setHeader("Vary", "User-Agent");

        //header("Pragma: ".$etag);

        $filename = $etag;
        if (isset($this->row["filename"])) {
            $filename = $this->row["filename"];
        }

        $this->setHeader("Content-Disposition", "{$this->disposition}; filename=\"$filename\"");

        $this->setHeader("Content-Transfer-Encoding", "binary");

    }

    /**
     * @throws Exception
     */
    public function send(bool $doExit = TRUE)
    {
        debug("Class: " . $this->className . " ID: " . $this->id . " Field: " . $this->field);

        //check auth_context field exists for this bean and authorize
        $this->authorizeAccess();

        //browser is sending ETag
        $requestETag = $this->requestETag();

        debug("Request ETag is: $requestETag");

        if (!$this->skip_cache) {
            if ($requestETag) {
                $cacheFile = new CacheFile($requestETag, $this->className, $this->id);
                if ($cacheFile->exists()) {
                    debug("Cache file exists for this ETag className and ID - sending 304 not modified only");
                    //exit with 304 not modified
                    $this->sendNotModified();
                }
                debug("Cache file does not exists for this ETag className and ID");
            }
        }

        //browser did not send ETag (browser have cache disabled?)
        //so load fully the bean data

        $this->loadBeanData();
        $this->unpackStorageObject();

        //calculate the ETag
        $this->fillHeaders();

        $cacheFile = new CacheFile($this->headers["ETag"], $this->className, $this->id);

        if (!$this->skip_cache) {
            //check if we have the ETag in cache so to skip image processing
            if ($cacheFile->exists()) {
                debug("Sending mathched ETag file from cache");
                $this->setHeader("X-Tag", "SparkCache");
                $this->sendFile($cacheFile->fileName());
            }
        }

        //do the magic - image resize etc
        $this->processData();

        //store to cache
        $cacheFile->store($this->data);

        $this->sendFile($cacheFile->fileName());

    }

    abstract protected function processData();

}