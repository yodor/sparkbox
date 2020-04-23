<?php
include_once("lib/handlers/JSONResponse.php");
include_once("lib/handlers/RequestHandler.php");

class RequestController
{

    protected static $ajax_handlers = array();
    protected static $request_handlers = array();

    protected static $working = false;

    public static function addAjaxHandler(IRequestProcessor $handler)
    {
        $command_name = $handler->getCommandName();

        self::$ajax_handlers[$command_name] = $handler;
    }

    public static function addRequestHandler(RequestHandler $handler)
    {
        self::$request_handlers[get_class($handler)] = $handler;
    }

    public static function findAjaxHandler($command_name)
    {

        if (isset(self::$ajax_handlers[$command_name])) {
            return self::$ajax_handlers[$command_name];
        }
        else {
            return NULL;
        }

    }

    public static function processAjaxHandlers()
    {
        if (self::$working === true) {
            debug("RequestController::processAjaxHandlers() Skip processing - Handler already working");
            return;
        }

        if (isset($_GET["ajax"])) {

            self::$working = true;
            // header("Pragma: no-cache");
            // header("Expires: 0");
            $processed = false;

            foreach (self::$ajax_handlers as $key => $handler) {
                debug("RequestController::CheckingHandler() $key=>" . get_class($handler));
                if ($handler->shouldProcess()) {

                    $ret = new JSONResponse("AjaxHandler");
                    try {
                        debug("RequestController::processAjaxHandlers() Processing Handler: " . get_class($handler));
                        $handler->processHandler();
                    }
                    catch (Exception $e) {
                        $ret->status = JSONResponse::STATUS_ERROR;
                        $ret->message = $e->getMessage();
                        $ret->response();
                    }
                    $processed = true;
                }
            }

            if (!$processed) {
                $ret = new JSONResponse("AjaxHandler");
                $ret->message = "Ajax Response requested but no handler processed this result. DEBUG: " . queryString($_GET);
                $ret->response();
            }
            exit;
        }

    }

    public static function processRequestHandlers()
    {
        $handler = false;
        $ret = false;

        foreach (self::$request_handlers as $key => $val) {

            if ($val->shouldProcess()) {
                $handler = $val;
                break;
            }

        }

        if ($handler) {

            try {
                $ret = $handler->processHandler();

            }
            catch (Exception $e) {
                Session::SetAlert($e->getMessage());

                if (strlen($handler->getCancelUrl()) > 0) {
                    header("Location: " . $handler->getCancelUrl());
                    exit;
                }
            }

        }
        return $ret;
    }

}

?>
