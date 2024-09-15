<?php
include_once("responders/RequestResponder.php");
include_once("responders/json/JSONResponse.php");

abstract class JSONResponder extends RequestResponder
{

    protected array $supported_content = array();
    protected string $content_type = "";

    public function __construct(string $cmd)
    {
        parent::__construct($cmd);

        $this->need_redirect = false;
        $this->supported_content = array();

        $class_methods = get_class_methods($this);
        foreach ($class_methods as $method) {
            if (str_starts_with($method, "__"))continue;
            if (!str_starts_with($method, "_"))continue;
            $supported_content = str_replace("_", "", $method);
            $this->supported_content[] = $supported_content;
        }

        debug(get_class($this)." accepting function calls: ", $this->supported_content);

    }

    protected function buildRedirectURL() : void
    {

    }

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
    {
        if (!isset($_REQUEST["type"])) throw new Exception("Parameter 'type' not specified");
        $content_type = $_REQUEST["type"];

        if (!in_array($content_type, $this->supported_content)) throw new Exception("Function call not supported");

        $this->content_type = $content_type;

        debug("Using function call: '$this->content_type'");
    }

    /**
     * Call the _named function passing a JSONResponse object as parameter
     * All properties set to the response are sent back as json_object
     * Capture output and set as property contents of the response
     */
    protected function processImpl() : void
    {

        $response = new JSONResponse(get_class($this));

        ob_start();
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

        if (isset($GLOBALS["DEBUG_JSONRESPONDER_OUTPUT"])) {
            debug("Response buffer: ".ob_get_contents());
        }

        ob_end_clean();
        $response->send();

    }

}

?>
