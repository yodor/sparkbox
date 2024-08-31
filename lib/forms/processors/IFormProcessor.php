<?php
include_once("forms/InputForm.php");

interface IFormProcessor
{

    const STATUS_ERROR = -1;
    const STATUS_NOT_PROCESSED = 0;
    const STATUS_OK = 1;

    public function process(InputForm $form) : void;

    /**
     * @return string
     */
    public function getMessage() : string;

    public function setMessage(string $message) : void;

    /**
     * @return int
     */
    public function getStatus() : int;

    /**
     * @param int $status
     */
    public function setStatus(int $status) : void;

}

?>
