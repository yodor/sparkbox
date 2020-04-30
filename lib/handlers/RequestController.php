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

    public static function findAjaxHandler($command_name) : IRequestProcessor
    {
        return self::$ajax_handlers[$command_name];
    }

    public static function findRequestHandler(string $className) : RequestHandler
    {
        return self::$request_handlers[$className];
    }

    public static function processAjaxHandlers()
    {
        if (self::$working === true) {
            debug("Handler already working. Nothing to do.");
            return;
        }

        if (isset($_GET["ajax"])) {

            self::$working = true;
            // header("Pragma: no-cache");
            // header("Expires: 0");
            $processed = false;

            foreach (array_keys(self::$ajax_handlers) as $idx => $commandName) {

                $handler = RequestController::findAjaxHandler($commandName);

                if ($handler->shouldProcess()) {

                    $ret = new JSONResponse("AjaxHandler");
                    try {
                        debug("Handler '".get_class($handler)."' accepted processing");
                        $handler->processHandler();
                    }
                    catch (Exception $e) {
                        $ret->status = JSONResponse::STATUS_ERROR;
                        $ret->message = $e->getMessage();
                        $ret->response();
                    }
                    $processed = true;
                }
                else {
                    debug("Handler '".get_class($handler)."' denied processing");
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
        $handler = NULL;

        foreach (array_keys(self::$request_handlers) as $idx => $className) {

            $requestHandler = RequestController::findRequestHandler($className);

            if ($requestHandler->shouldProcess()) {
                $handler = $requestHandler;
                break;
            }

        }

        if ($handler) {

            try {
                $handler->processHandler();

            }
            catch (Exception $e) {
                Session::SetAlert($e->getMessage());

                if (strlen($handler->getCancelUrl()) > 0) {
                    header("Location: " . $handler->getCancelUrl());
                    exit;
                }
            }

        }

    }

}

?>
