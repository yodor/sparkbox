<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/InputField.php");

interface IFormRenderer 
{
    public function renderSubmitLine(InputForm $form);
    public function renderForm(InputForm $form);
    public function getSubmitName(InputForm $form);
    public function renderField(InputField $field);
}

?>