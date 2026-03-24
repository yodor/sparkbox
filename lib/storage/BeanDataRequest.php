<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/ErrorResponse.php");
include_once("storage/ImageDataResponse.php");
include_once("storage/FileDataResponse.php");

class BeanDataRequest
{
    const string KEY_CMD = "cmd";
    const string KEY_ID = "id";
    const string KEY_CLASS = "class";

    const string CMD_DATA = "data";
    const string CMD_PHOTO = "image";

    const string KEY_ROUTE = "route";

    protected string $cmd = "";
    protected array $supported_commands = array(BeanDataRequest::CMD_DATA, BeanDataRequest::CMD_PHOTO);


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
            $this->cmd = $_GET[BeanDataRequest::KEY_CMD];

            if (!in_array($this->cmd, $this->supported_commands)) {
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

            $resp = NULL;

            if (strcmp($this->cmd, BeanDataRequest::CMD_DATA) === 0) {
                $resp = new FileDataResponse($id, $className);
            }
            else if (strcmp($this->cmd, BeanDataRequest::CMD_PHOTO) === 0) {
                $resp = new ImageDataResponse($id, $className);
            }
            if (!($resp instanceof BeanDataResponse)) {
                throw new Exception("Unable to construct BeanDataResponse");
            }

            $resp->send();

        }
        catch (Exception $e) {
            Debug::ErrorLog("Exception processing this request: ".$e->getTraceAsString());
            $resp = new ErrorResponse();
            $resp->sendException($e);
        }

    }

}