<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/InputField.php");
require_once("lib/securimage/securimage.php");

class CaptchaInputValidator implements IInputValidator
{

    public function validateInput(InputField $field)
    {

        $value = $field->getValue();
        
        $securimage = new Securimage();

        if ($securimage->check($field->getValue()) == false) {
            throw new Exception("Неправилен код за сигурност!");
        }

    }

}
?>