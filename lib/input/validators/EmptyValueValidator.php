<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class EmptyValueValidator implements IInputValidator
{
    public $require_array_value = FALSE;

    public function validate(DataInput $input)
    {

        $value = $input->getValue();

        //checkbox and radios receive array of values here
        if (is_array($value)) {

            if ($input->isRequired()) {
                if (count($value) < 1) throw new Exception("Input value");
                $empty_count = 0;
                foreach ($value as $idx => $val) {
                    if (strlen(trim($val)) == 0 && $this->require_array_value) $empty_count++;
                }
                if ($empty_count == count($value)) throw new Exception("Input value");
            }

        }
        else {

            if (strlen(trim($value)) == 0 && $input->isRequired()) {
                throw new Exception("Input value");
            }

        }

    }

}

?>