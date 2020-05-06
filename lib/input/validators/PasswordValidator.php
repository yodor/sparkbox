<?php
include_once("input/validators/EmptyValueValidator.php");
include_once("input/DataInput.php");

class PasswordValidator extends EmptyValueValidator
{
    public function validate(DataInput $input)
    {

        parent::validate($input);


    }

}

?>