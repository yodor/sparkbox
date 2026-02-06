<?php
include_once("input/validators/IInputValidator.php");

class SimpleTextValidator implements IInputValidator
{


    /**
     * @param DataInput $input
     * @return void
     * @throws Exception
     */
    public function validate(DataInput $input) : void
    {
        $value = (string)$input->getValue();

        if (strcmp($value, Spark::AttributeValue($value)) != 0) {
            throw new Exception("Only letters and space accepted here");
        }

    }
}
?>
