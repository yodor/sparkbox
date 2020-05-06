<?php
include_once ("storage/HTTPResponse.php");

class ErrorResponse extends HTTPResponse
{
    public function __construct($message)
    {
        if ($message instanceof Exception) {
            $message = $message->getMessage();
        }
        $this->setHeader("Content-Type", "text/html");
        $this->setData($message, strlen($message));
    }

}

?>