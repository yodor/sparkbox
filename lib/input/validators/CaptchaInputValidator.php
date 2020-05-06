<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");
require_once("securimage/securimage.php");

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
