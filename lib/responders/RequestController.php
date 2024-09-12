<?php
include_once("responders/RequestResponder.php");
include_once("responders/json/JSONResponder.php");
include_once("responders/json/JSONResponse.php");

class RequestController
{

    protected static $responders = array();

    public static function isJSONRequest(): bool
    {
        return isset($_REQUEST["JSONRequest"]);
    }

    /**
     * Register responder with controller using its command
     * Existing command registration will be replaced
     * @param RequestResponder $responder
     * @return void
     */
    public static function Add(RequestResponder $responder): void
    {
        $command_name = $responder->getCommand();
        self::$responders[$command_name] = $responder;
        debug("Command: [$command_name] => ".get_class($responder));
    }

    /**
     * Remove responder registration
     * @param RequestResponder $responder
     * @return void
     */
    public static function Remove(RequestResponder $responder): void
    {
        $command_name = $responder->getCommand();
        if (isset(self::$responders[$command_name])) {
            debug("Command: [$command_name] => ".get_class($responder));
        }
    }

    /**
     * Get the responder registered with command '$command'
     * @param string $command
     * @return RequestResponder
     * @throws Exception
     */
    public static function Get(string $command): RequestResponder
    {
        if (!isset(self::$responders[$command])) throw new Exception("RequestResponder for command: '$command' not found");
        return self::$responders[$command];
    }

    /**
     * Return true if command '$command' have registered responder
     * @param string $command
     * @return bool
     */
    public static function Have(string $command): bool
    {
        return isset(self::$responders[$command]);
    }

    public static function Process()
    {
        $isJson = RequestController::isJSONRequest();

        $commands = array_keys(self::$responders);

        debug("Registered commands: ", $commands);

        $request_responder = null;

        foreach ($commands as $idx => $command) {
            $responder = RequestController::Get($command);
            if ($isJson xor ($responder instanceof JSONResponder)) continue;
            if (!$responder->accept()) continue;
            $request_responder = $responder;
            break;
        }

        if (is_null($request_responder)) {
            debug("No responder accepted this request");
            if ($isJson) {
                $ret = new JSONResponse("RequestController");
                $ret->message = "No responder is registered to process this request";
                $ret->send();
                exit;
            }
            return;
        }

        //
        try {
            debug("Responder " . get_class($request_responder) . " accepted processing. Is JSON: ". ($isJson?"YES":"NO"));
            $request_responder->process();
        }
        catch (Exception $e) {
            debug("Error processing this responder: ".$e->getMessage());
            if ($isJson) {
                $ret = new JSONResponse("RequestController");
                $ret->status = JSONResponse::STATUS_ERROR;
                $ret->message = $e->getMessage();
                $ret->send();
            }
            else {
                Session::SetAlert("Error processing this request: "."<BR>".$e->getMessage());
            }
        }

        if ($isJson) {
            exit;
        }


    }

}

?>
