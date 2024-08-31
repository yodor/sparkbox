<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class NumericValidator implements IInputValidator
{

    public $allow_zero = FALSE;
    public $allow_negative = FALSE;

    public function __construct($allow_zero = FALSE, $allow_negative = FALSE)
    {
        $this->allow_zero = $allow_zero;
        $this->allow_negative = $allow_negative;

    }

    public function validate(DataInput $input) : void
    {
        $value = $input->getValue();

        if (mb_strlen($value) == 0) {
            if ($input->isRequired()) throw new Exception("Input numeric value");
        }
        else {

            $check = filter_var($value, FILTER_VALIDATE_FLOAT);
            if ($check === false) throw new Exception("Input numeric value");
            if (!$this->allow_zero && $check == 0) throw new Exception("Input non-zero value");
            if (!$this->allow_negative && $check < 0) throw new Exception("Input non-negative value");

        }

    }

}

?>
