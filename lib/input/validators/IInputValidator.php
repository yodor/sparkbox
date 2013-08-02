<?php
include_once("lib/input/InputField.php");

interface IInputValidator 
{
  public function validateInput(InputField $field);


}