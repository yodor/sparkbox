<?php
include_once ("lib/storage/BeanDataResponse.php");
include_once ("lib/storage/ErrorResponse.php");
include_once ("lib/storage/ImageDataResponse.php");
include_once ("lib/storage/FileDataResponse.php");

class BeanDataRequest
{
    const KEY_CMD = "cmd";
    const KEY_ID = "id";
    const KEY_CLASS = "class";

    const CMD_DATA = "data";
    const CMD_PHOTO = "image";

    protected $cmd = "";
    protected $supported_commands = array(BeanDataRequest::CMD_DATA, BeanDataRequest::CMD_PHOTO);

    public function __construct()
    {
        debug("...");

        try {

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

            $resp = null;

            if (strcmp($this->cmd, BeanDataRequest::CMD_DATA)==0) {
                $resp = new FileDataResponse($id, $className);
            }
            else if (strcmp($this->cmd, BeanDataRequest::CMD_PHOTO)==0) {
                $resp = new ImageDataResponse($id, $className);
            }
            if (! ($resp instanceof BeanDataResponse)) {
                throw new Exception("Unable to construct BeanDataResponse");
            }

            $resp->skip_cache = true;
            $resp->send();

        }
        catch (Exception $e) {
            $resp = new ErrorResponse($e);
            $resp->send();
        }

    }


}