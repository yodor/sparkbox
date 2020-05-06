<?php
include_once("forms/InputForm.php");


interface IFormProcessor
{

    const STATUS_ERROR = -1;
    const STATUS_NOT_PROCESSED = 0;
    const STATUS_OK = 1;

    public function processForm(InputForm $form, string $submit_name = "");

    /**
     * @return string
     */
    public function getMessage();

    public function setMessage(string $message);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $status
     */
    public function setStatus(int $status);

}

?>