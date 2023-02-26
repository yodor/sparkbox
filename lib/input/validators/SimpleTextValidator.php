<?php
include_once("input/validators/IInputValidator.php");

class SimpleTextValidator implements IInputValidator
{

    /**
     * Validate value of DataInput is correct
     * @param DataInput $input
     * @return mixed
     */
    public function validate(DataInput $input)
    {
        $value = $input->getValue();

        if (strcmp($value, attributeValue($value)) == 0) {

        }
        else {
            throw new Exception("Only letters and space accepted here");
        }
    }
}
?>