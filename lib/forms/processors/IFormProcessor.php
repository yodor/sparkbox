<?php
include_once("lib/forms/InputForm.php");


interface IFormProcessor
{

    const STATUS_ERROR = -1;
    const STATUS_NOT_PROCESSED = 0;
    const STATUS_OK = 1;

    public function processForm(InputForm $form, $submit_name = "");

    public function getMessage();

    public function setMessage($message);

    public function getStatus();

    public function setStatus($status);

}

?>