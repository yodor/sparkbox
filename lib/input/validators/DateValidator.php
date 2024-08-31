<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class DateValidator implements IInputValidator
{

    public static function isValidDate($value)
    {
        $pieces = explode("-", $value);

        if (count($pieces) != 3) {

            return FALSE;
        }

        $ret = FALSE;
        try {
            $year = $pieces[0];
            $month = $pieces[1];
            $day = $pieces[2];
            DateValidator::validateDate($year, $month, $day);
            $ret = TRUE;
        }
        catch (Exception $e) {

        }
        return $ret;
    }

    public static function getTimestamp($field_value)
    {
        $ret = 0;
        if (DateValidator::isValidDate($field_value)) {
            $pieces = explode("-", $field_value);
            $year = $pieces[0];
            $month = $pieces[1];
            $day = $pieces[2];
            $ret = strtotime("$year-$month-$day");
        }
        return $ret;

    }

    protected static function validateDate($year, $month, $day)
    {

        if ($year < 1) {
            throw new Exception("Incorrect Date: Year");
        }

        if ($month < 1 || $month > 12) {
            throw new Exception("Incorrect Date: Month");

        }

        if ($day < 1 || $day > 31) {
            throw new Exception("Incorrect Date: Day");
        }

        $days_in_month = date("t", strtotime($year . "-" . $month . "-" . "01"));

        if ($day > $days_in_month) {
            throw new Exception("Incorrect Date: Day > $days_in_month");
        }

        $u_date = strtotime($year . "-" . $month . "-" . $day);
        if ($u_date === FALSE) {
            throw new Exception("Incorrect Date");
        }

    }

    public function validate(DataInput $input) : void
    {

        $pieces = explode("-", $input->getValue());

        if (count($pieces) != 3) {
            throw new Exception("Incorrect date (date format)");
        }

        $year = $pieces[0];
        $month = $pieces[1];
        $day = $pieces[2];

        try {
            DateValidator::validateDate($year, $month, $day);
        }
        catch (Exception $e) {
            if (strlen($year) > 0 || strlen($month) > 0 || strlen($day) > 0 || $input->isRequired()) {
                throw  $e;
            }
            else {
                //is required already false
                $input->clear();

            }
        }

    }

}

?>
