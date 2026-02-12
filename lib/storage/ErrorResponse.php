<?php
include_once("storage/SparkHTTPResponse.php");

class ErrorResponse extends SparkHTTPResponse
{
    protected DataBuffer $buffer;
    public function __construct()
    {
        parent::__construct();

        $this->buffer = new DataBuffer();

        $this->setHeader("Cache-Control", "no-cache, must-revalidate");

    }
    public function sendException(Exception $ex) : void
    {
        $this->sendMessage($ex->getMessage());
    }
    public function sendMessage(string $message) : void
    {
        $this->buffer->setData($message);
        $this->sendData($this->buffer);
    }

}