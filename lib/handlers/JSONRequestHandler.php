<?php
include_once("beans/SiteTextsBean.php");
include_once("beans/TranslationBeansBean.php");
include_once("handlers/RequestHandler.php");

abstract class JSONRequestHandler extends RequestHandler
{

    protected $supported_content = NULL;
    protected $content_type = "";
    protected $response_send = FALSE;

    public function __construct(string $cmd)
    {
        parent::__construct($cmd);

        $this->supported_content = array();

        $class_methods = get_class_methods($this);
        foreach ($class_methods as $key => $fname) {
            if (strpos($fname, "_") === 0 && strpos($fname, "__") === FALSE) {
                $supported_content = str_replace("_", "", $fname);
                $this->supported_content[] = $supported_content;
            }
        }

        debug("Accepting commands: ", $this->supported_content);

    }

    protected function parseParams()
    {

        if (!isset($_GET["type"])) throw new Exception("Command 'type' parameter not passed");
        $content_type = $_GET["type"];

        if (!in_array($content_type, $this->supported_content)) throw new Exception("Command not supported");

        $this->content_type = $content_type;

        debug("Requested command type: '{$this->content_type}'");
    }

    protected function process()
    {

        $ret = new JSONResponse(get_class($this) . "Response");

        ob_start();

        register_shutdown_function(array($this, "shutdown"));

        try {

            $function_name = "_" . $this->content_type;

            if (is_callable(array($this, $function_name))) {
                $this->$function_name($ret);
            }
            else {
                throw new Exception("Function: $function_name not callable");
            }

            $ret->contents = ob_get_contents();
            $ret->status = JSONResponse::STATUS_OK;

        }
        catch (Exception $e) {

            debug("Exception during process: " . $e->getMessage());

            $ret->contents = "";
            $ret->status = JSONResponse::STATUS_ERROR;
            $ret->message = $e->getMessage();

        }

        ob_end_clean();
        $ret->response();
        $this->response_send = TRUE;

    }

    public function shutdown()
    {
        $err = error_get_last();

        //if response is sent last error is proably not fatal
        debug($this, "Response_send = " . (int)$this->response_send);

        if (is_array($err)) {

            debug($this, "error_get_last: ", $err);

            if (!$this->response_send) {

                @ob_end_clean();

                $ret = new JSONResponse(get_class($this) . "Response");
                $ret->status = JSONResponse::STATUS_ERROR;
                $ret->message = "Error: " . $err["type"] . " - " . $err["message"] . "<BR>File: " . $err["file"] . " Line: " . $err["line"];
                $ret->response();
                $ret->contents = "";
            }

        }
        exit;
    }
}

?>
