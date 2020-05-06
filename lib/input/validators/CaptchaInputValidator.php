<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/DataInput.php");
require_once("lib/securimage/securimage.php");

class CaptchaInputValidator implements IInputValidator
{

    public function validate(DataInput $input)
    {

        $value = $input->getValue();

        $securimage = new Securimage();

        if ($securimage->check($input->getValue()) == false) {
            throw new Exception("Неправилен код за сигурност!");
        }

    }

}

?>
