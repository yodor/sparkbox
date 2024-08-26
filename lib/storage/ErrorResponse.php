<?php
include_once("storage/SparkHTTPResponse.php");

class ErrorResponse extends SparkHTTPResponse
{
    public function __construct($message)
    {
        parent::__construct();

        if ($message instanceof Exception) {
            $message = $message->getMessage();
        }
        debug("ErrorResponse: ".$message);

        $this->setHeader("Cache-Control", "no-cache, must-revalidate");
        $this->setHeader("Content-Type", "text/html");

        $this->setData($message, strlen($message));
    }

}

?>
