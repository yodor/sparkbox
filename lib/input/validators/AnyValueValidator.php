<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

/**
 * Class AnyValueValidator
 * Dummy validator returning always true. Used in login/register forms to skip validation of the passwords fields that are sent hashed in other field
 */
class AnyValueValidator implements IInputValidator
{

    public function validate(DataInput $input)
    {
        return true;
    }

}

?>