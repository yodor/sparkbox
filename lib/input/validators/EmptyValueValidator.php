<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class EmptyValueValidator implements IInputValidator
{
    public bool $require_array_value = FALSE;

    public function validate(DataInput $input) : void
    {
        //not required = any value
        if (!$input->isRequired()) return;

        $value = $input->getValue();

        //checkbox and radio have array as value
        if (is_array($value)) {
            if (count($value) < 1) throw new Exception("Value required");

        }
        else {
            if (strlen(trim($value)) == 0) {
                throw new Exception("Input value");
            }
        }

    }

}

?>
