<?php
include_once("responders/RequestResponder.php");
include_once("responders/json/JSONResponder.php");
include_once("responders/json/JSONResponse.php");

class RequestController
{

    protected static $responders = array();

    public static function isJSONRequest() : bool
    {
        return URL::Current()->contains("JSONRequest");
    }

    public static function Add(RequestResponder $responder) : void
    {
        $command_name = $responder->getCommand();
        self::$responders[$command_name] = $responder;
        debug("Adding RequestResponder: '" . get_class($responder) . "' for command: '$command_name'");
    }

    public static function Remove(RequestResponder $responder) : void
    {
        $command_name = $responder->getCommand();
        if (isset(self::$responders[$command_name])) {
            debug("Removing RequestResponder: '" . get_class($responder) . "' for command: '$command_name'");
        }
    }

    public static function Get(string $command): RequestResponder
    {
        if (!isset(self::$responders[$command])) throw new Exception("RequestResponder for command: '$command' not found");
        return self::$responders[$command];
    }

    public static function Process()
    {
        $isJson = RequestController::isJSONRequest();

        $commands = array_keys(self::$responders);

        $request_responder = null;

        foreach ($commands as $idx => $command) {
            $responder = RequestController::Get($command);
            if ($isJson) {
                if (! ($responder instanceof JSONResponder)) continue;
            }
            else {
                if ($responder instanceof JSONResponder) continue;
            }
            if (!$responder->needProcess()) continue;
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
            debug("Responder '" . get_class($request_responder) . "' accepted processing. Is JSON: $isJson");
            $request_responder->processInput();
        }
        catch (Exception $e) {
            if ($isJson) {
                $ret = new JSONResponse("RequestController");
                $ret->status = JSONResponse::STATUS_ERROR;
                $ret->message = $e->getMessage();
                $ret->send();
            }
            else {
                debug("RequestResponder error: ".$e->getMessage());
                Session::SetAlert("Error processing this request: "."<BR>".$e->getMessage());
            }
        }

        if ($isJson) {
            exit;
        }


    }

}

?>
