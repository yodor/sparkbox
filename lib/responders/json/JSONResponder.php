<?php
include_once("responders/RequestResponder.php");
include_once("responders/json/JSONResponse.php");

abstract class JSONResponder extends RequestResponder
{

    //!should match JSONRequest KEY_FUNCTION
    const string KEY_FUNCTION = "function";
    const string KEY_JSONREQUEST = "JSONRequest";

    /**
     * Contains all remote callable method names.
     * Object methods names starting with '_' are allowed
     * @var array
     */
    protected array $functions = array();

    /**
     * Current request function call name - should be in $function for successful call
     * @var string
     */
    protected string $requestFunction = "";

    public function __construct()
    {
        parent::__construct();

        $this->need_redirect = false;
        $this->functions = array();

        $classMethods = get_class_methods($this);
        foreach ($classMethods as $method) {
            if (str_starts_with($method, "__"))continue;
            if (!str_starts_with($method, "_"))continue;
            $method = str_replace("_", "", $method);
            $this->functions[] = $method;
        }

        debug($this->getName()." enabled functions: ", $this->functions);

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
        if (!isset($_REQUEST[JSONResponder::KEY_FUNCTION])) throw new Exception("Request parameter '".JSONResponder::KEY_FUNCTION."' not found");
        $requestFunction = $_REQUEST[JSONResponder::KEY_FUNCTION];

        if (!in_array($requestFunction, $this->functions)) throw new Exception("Function not supported");

        $this->requestFunction = "_".$requestFunction;

        debug("Request function is: '$this->requestFunction'");
    }

    /**
     * Call the _named function passing a JSONResponse object as parameter
     * All properties set to the response are sent back as json_object
     * Capture output and set as property contents of the response
     */
    protected function processImpl() : void
    {

        $response = new JSONResponse($this->getName());

        ob_start();
        try {

            if (is_callable(array($this, $this->requestFunction))) {
                $this->{$this->requestFunction}($response);
            }
            else {
                throw new Exception("Function: '{$this->requestFunction}' not callable");
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
        ob_end_clean();

        $response->send();

    }

}

?>
