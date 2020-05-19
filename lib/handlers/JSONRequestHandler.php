<?php
include_once("beans/SiteTextsBean.php");
include_once("beans/TranslationBeansBean.php");
include_once("handlers/RequestHandler.php");

abstract class JSONRequestHandler extends RequestHandler
{

    protected $supported_content = NULL;
    protected $content_type = "";
    protected $response_send = FALSE;

    protected $need_redirect = false;

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

        debug("Accepting function calls: ", $this->supported_content);

    }

    protected function parseParams()
    {

        if (!isset($_GET["type"])) throw new Exception("Command 'type' parameter not passed");
        $content_type = $_GET["type"];

        if (!in_array($content_type, $this->supported_content)) throw new Exception("Command not supported");

        $this->content_type = $content_type;

        debug("Requested function call: '{$this->content_type}'");
    }

    /**
     * Call the _named function passing a JSONResponse object as parameter
     * All properties set to the response are sent back to the JS as json_object
     */
    protected function processImpl()
    {

        $response = new JSONResponse(get_class($this) . "Response");

        ob_start();

        register_shutdown_function(array($this, "shutdown"));

        try {

            $function_name = "_" . $this->content_type;

            if (is_callable(array($this, $function_name))) {
                $this->$function_name($response);
            }
            else {
                throw new Exception("Function: '$function_name' not callable");
            }

            $response->contents = ob_get_contents();
            $response->status = JSONResponse::STATUS_OK;

        }
        catch (Exception $e) {

            debug("Exception during process: " . $e->getMessage());

            $response->contents = "";
            $response->status = JSONResponse::STATUS_ERROR;
            $response->message = $e->getMessage();

        }

        debug("Response buffer: ".ob_get_contents());

        ob_end_clean();
        $response->send();
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

                $response = new JSONResponse(get_class($this) . "Response");
                $response->status = JSONResponse::STATUS_ERROR;
                $response->message = "Error: " . $err["type"] . " - " . $err["message"] . "<BR>File: " . $err["file"] . " Line: " . $err["line"];
                $response->send();
                $response->contents = "";
            }

        }
        exit;
    }
}

?>
