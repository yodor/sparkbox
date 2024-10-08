<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class TimeValidator implements IInputValidator
{

    public static function isValidTime($value)
    {
        $pieces = explode(":", $value);

        if (count($pieces) != 2) {

            return FALSE;
        }

        $ret = FALSE;
        try {
            $hour = $pieces[0];
            $minute = $pieces[1];

            TimeValidator::validateTime($hour, $minute);
            $ret = TRUE;
        }
        catch (Exception $e) {

        }
        return $ret;
    }

    protected static function validateTime($hour, $minute)
    {

        if ($hour < 0 || $hour > 23) {
            throw new Exception("Incorrect time: hour");
        }

        if ($minute < 0 || $minute > 59) {
            throw new Exception("Incorrect time: minute");

        }

    }

    public function validate(DataInput $input) : void
    {

        $pieces = explode(":", $input->getValue());

        if (count($pieces) != 2) {
            throw new Exception("Incorrect time");

        }

        $hour = $pieces[0];
        $minute = $pieces[1];

        TimeValidator::validateTime($hour, $minute);

    }

}

?>
