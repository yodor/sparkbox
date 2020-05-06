<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class NumericValidator implements IInputValidator
{

    public $allow_zero = false;
    public $allow_negative = false;

    public function __construct($allow_zero = false, $allow_negative = false)
    {
        $this->allow_zero = $allow_zero;
        $this->allow_negative = $allow_negative;

    }

    public function validate(DataInput $input)
    {
        $value = $input->getValue();

        if (strlen($value) === 0) {
            if ($input->isRequired()) throw new Exception("Input numeric value");
        }
        else {

            if (!preg_match("/^([0-9.\ ])+$/", $value)) throw new Exception("Input numeric value");

        }

    }

}

?>