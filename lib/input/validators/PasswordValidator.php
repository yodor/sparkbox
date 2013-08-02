<?php
include_once("lib/input/validators/EmptyValueValidator.php");
include_once("lib/input/InputField.php");

class PasswordValidator extends EmptyValueValidator
{
  public function validateInput(InputField $field)
  {
	
	  parent::validateInput($field);
	  
	  
  }

}
?>