<?php
include_once("lib/input/validators/EmptyValueValidator.php");
include_once("lib/input/DataInput.php");

class PasswordValidator extends EmptyValueValidator
{
    public function validateInput(DataInput $field)
    {

        parent::validateInput($field);


    }

}

?>