<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class TextCaptchaValidator implements IInputValidator
{

    public function __construct()
    {

    }

    public function validate(DataInput $input)
    {


        $rend = $input->getRenderer();

        if (!($rend instanceof TextCaptchaField)) {
            throw new Exception("Incorrect validator for this field");
        }
        $value = $input->getValue();

        if ($value == $rend->getResult()) {
            $rend->resetResult();
        }
        else {

            throw new Exception("Incorrect result");
        }


    }

}

?>