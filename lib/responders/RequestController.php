<?php
include_once("responders/RequestResponder.php");
include_once("responders/json/JSONResponder.php");
include_once("responders/json/JSONResponse.php");

class RequestController
{

    protected static $responders = array();

    public static function Add(RequestResponder $responder)
    {
        $command_name = $responder->getCommand();
        self::$responders[$command_name] = $responder;

        debug("RequestResponder: '" . get_class($responder) . "' accepting command: '$command_name'");
    }

    public static function Get(string $command): RequestResponder
    {
        if (!isset(self::$responders[$command])) throw new Exception("RequestResponder for command: '$command' not found");
        return self::$responders[$command];
    }

    public static function processJSONResponders()
    {

        $commands = array_keys(self::$responders);
        foreach ($commands as $idx => $command) {

            $request_responder = RequestController::Get($command);
            if (!($request_responder instanceof JSONResponder)) continue;

            if ($request_responder->needProcess()) {

                $ret = new JSONResponse("RequestController");
                try {
                    debug("Handler '" . get_class($request_responder) . "' accepted processing");
                    $request_responder->processInput();
                }
                catch (Exception $e) {
                    //default error response
                    $ret->status = JSONResponse::STATUS_ERROR;
                    $ret->message = $e->getMessage();
                    $ret->send();
                }
                exit;
            }
            else {
                debug("Handler '" . get_class($request_responder) . "' denied processing");
            }

        }

    }

    public static function processResponders()
    {

        $commands = array_keys(self::$responders);
        foreach ($commands as $idx => $command) {
            $request_responder = RequestController::Get($command);
            //skip JSONResponders
            if ($request_responder instanceof JSONResponder) continue;

            if ($request_responder->needProcess()) {
                debug("RequestResponder '" . get_class($request_responder) . "' accepted input processing");
                $request_responder->processInput();
                break;
            }
            else {
                debug("RequestResponder '" . get_class($request_responder) . "' denied input processing");
            }

        }

    }

}

?>
