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

        Debug::ErrorLog($this->getName()." enabled functions: ", $this->functions);

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

        Debug::ErrorLog("Request function is: '$this->requestFunction'");
    }

    /**
     * Call the _named function passing a JSONResponse object as parameter
     * All properties set to the response are sent back as json_object
     * Capture output and set as property contents of the response
     */
    protected function processImpl() : void
    {

        $response = new JSONResponse($this->getName());

        /**
         * In PHP, fatal errors (E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, and E_COMPILE_WARNING)
         * cannot be caught using a traditional try-catch block
         * --- as we are inside ob_start non-fatal errors will be output in the buffer and send inside the response contents
         *
         * However, you can use the set_error_handler function to register a custom error handler that will be called for
         * non-fatal errors, and then use the register_shutdown_function to catch fatal errors that occur during script execution.
         * --- buffer already have contents and fatal error occurred php will call registered shutdown function after buffer flush/clean
         * so register ob_callback function to handle fatal errors and send back to client inside 'message'
         */

        $obCallback = function(string $buffer) use ($response) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
                // The error is fatal - replace contents that might already be into the output buffer
                $response->message =  "Fatal Error: $error[message] ($error[type]) in $error[file] on line $error[line]\n";
                $response->status = JSONResponse::STATUS_ERROR;
                $response->sendHeaders();
                return json_encode($response);
            }
            // The error is not fatal (e.g., warning, notice, etc.)
            //send the original input to client
            return $buffer;
        };


        //fatal errors will be handled inside obCallback
        ob_start($obCallback);

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

            Debug::ErrorLog("Exception during process: " . $e->getMessage());

            $response->contents = "";
            $response->status = JSONResponse::STATUS_ERROR;
            $response->message = $e->getMessage();

        }
        ob_end_clean();

        $response->send();

    }

}