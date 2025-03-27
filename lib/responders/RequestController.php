<?php
include_once("responders/RequestResponder.php");
include_once("responders/json/JSONResponder.php");
include_once("responders/json/JSONResponse.php");

class RequestController
{



    protected static $responders = array();

    public static function isJSONRequest(): bool
    {
        return isset($_REQUEST[JSONResponder::KEY_JSONREQUEST]);
    }

    /**
     * Register responder with controller using its command
     * Existing command registration will be replaced
     * @param RequestResponder $responder
     * @return void
     */
    public static function Add(RequestResponder $responder): void
    {
        $name = $responder->getName();
        self::$responders[$name] = $responder;
        debug("Adding: '$name'");
    }

    /**
     * Remove responder registration
     * @param RequestResponder $responder
     * @return void
     */
    public static function Remove(RequestResponder $responder): void
    {
        $name = $responder->getName();
        if (isset(self::$responders[$name])) {
            debug("Removing: '$name'");
        }
    }

    /**
     * Get the responder registered with command '$command'
     * @param string $name
     * @return RequestResponder
     * @throws Exception
     */
    public static function Get(string $name): RequestResponder
    {
        if (!isset(self::$responders[$name])) throw new Exception("RequestResponder '$name' not found");
        return self::$responders[$name];
    }

    /**
     * Return true if command '$command' have registered responder
     * @param string $name
     * @return bool
     */
    public static function Have(string $name): bool
    {
        return isset(self::$responders[$name]);
    }

    public static function Process()
    {
        $isJson = RequestController::isJSONRequest();

        $names = array_keys(self::$responders);

        debug("Registered responders: ", $names);

        $request_responder = null;

        foreach ($names as $name) {
            $responder = RequestController::Get($name);
            if ($isJson xor ($responder instanceof JSONResponder)) continue;
            if (!$responder->accept()) continue;
            $request_responder = $responder;
            break;
        }

        //
        $exception = null;

        try {

            if (is_null($request_responder)) {
                debug("No responder accepted this request: ".URL::Current());
                if ($isJson) throw new Exception("No responder is registered to process this request");

                return;
            }

            debug("Responder " . get_class($request_responder) . " accepted processing. Is JSON: ". ($isJson?"YES":"NO"));
            $request_responder->process();
        }
        catch (Exception $e) {
            debug("Error processing this responder: ".$e->getMessage());
            $exception = $e;
        }

        if ($isJson) {
            if ($exception instanceof Exception) {
                $ret = new JSONResponse("RequestController");
                $ret->status = JSONResponse::STATUS_ERROR;
                $ret->message = $exception->getMessage();
                $ret->send();
            }
            exit;
        }

        $redirectURL = null;

        if ($request_responder->needRedirect()) {
            $redirectURL = new URL($request_responder->getSuccessUrl());
        }

        if ($exception instanceof Exception) {
            Session::SetAlert($exception->getMessage());
            $redirectURL = new URL($request_responder->getCancelUrl());
        }

        if ($redirectURL instanceof URL) {
            debug("Redirecting to URL: ".$redirectURL);
            header("Location: " . $redirectURL);
            exit;
        }
        else {
            debug("Redirect URL is not set");
        }

    }

}

?>
