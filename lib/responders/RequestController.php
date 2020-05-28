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

        $ret = new JSONResponse("RequestController");
        $ret->status = JSONResponse::STATUS_ERROR;

        foreach ($commands as $idx => $command) {

            $request_responder = RequestController::Get($command);
            if (!($request_responder instanceof JSONResponder)) continue;

            if ($request_responder->needProcess()) {

                try {
                    debug("Responder '" . get_class($request_responder) . "' accepted processing");
                    $request_responder->processInput();
                }
                catch (Exception $e) {
                    $ret->message = $e->getMessage();
                    $ret->send();
                }
                exit;
            }
            else {
                debug("Responder '" . get_class($request_responder) . "' refused processing");
            }

        }

        //request contains JSONRequest but no handler accepted it - send error
        $ret = new JSONResponse("RequestController");
        $ret->message = "No responder is registered to process this request";
        $ret->send();
        exit;
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
                try {
                    $request_responder->processInput();
                }
                catch (Exception $e) {
                    debug("RequestResponder error: ".$e->getMessage());
                    Session::SetAlert("Error processing this request: "."<BR>".$e->getMessage());
                }
                break;
            }
            else {
                debug("RequestResponder '" . get_class($request_responder) . "' denied input processing");
            }

        }

    }

}

?>
