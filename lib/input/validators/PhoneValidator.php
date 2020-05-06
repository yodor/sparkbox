<?php
include_once("input/DataInput.php");
include_once("input/validators/IInputValidator.php");

class PhoneValidator implements IInputValidator
{

    /**
     * @param DataInput $input
     * @throws Exception
     */
    public function validate(DataInput $input)
    {

        $pieces = explode("|", $input->getValue());

        if (count($pieces) != 3) {
            throw new Exception("Incorrect phone");

        }

        $country_code = $pieces[0];
        $city_code = $pieces[1];
        $phone_code = $pieces[2];

        $ret = true;

        $have_entry = (strlen($country_code) > 0 || strlen($city_code) > 0 || strlen($phone_code) > 0);

        $err = "";

        if ($input->isRequired() || $have_entry) {
            if ((int)$country_code < 1 || !preg_match("/^([0-9])+$/", $country_code)) {
                $err = "Input country code";
                $ret = false;
            }

            if ((int)$city_code < 1 || !preg_match("/^([0-9])+$/", $city_code)) {
                $err = "Input city code";
                $ret = false;
            }

            if ((int)$phone_code < 1 || !preg_match("/^([0-9])+$/", $phone_code)) {
                $err = "Input phone number";
                $ret = false;
            }
        }
        if (!$ret) {
            throw new Exception($err);
        }

    }

}

?>