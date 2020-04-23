<?php
include_once("lib/input/renderers/InputField.php");

class PhoneField extends InputField
{


    public function __construct()
    {
        parent::__construct();


        $this->is_compound = true;
    }

    public function renderImpl()
    {


        $field_name = $this->field->getName();
        $field_value = $this->field->getValue();


        $pieces = explode("|", $field_value);

        $country_code = "";
        $city_code = "";
        $phone_code = "";
        if (count($pieces) == 3) {
            $country_code = $pieces[0];
            $city_code = $pieces[1];
            $phone_code = $pieces[2];
        }

        echo "<div class='FieldElements'>";

        // 	$fvalue=htmlentities(mysql_real_unescape_string($country_code),ENT_QUOTES,"UTF-8");

        echo "<input class='PhonePart CountryCode' type=text  name='country_{$field_name}'  value='$country_code' tooltip='Country Code'>";

        // 	$fvalue=htmlentities(mysql_real_unescape_string($city_code),ENT_QUOTES,"UTF-8");
        echo "<input class='PhonePart CityCode' type=text  name='city_{$field_name}' value='$city_code' tooltip='City/Area Code'>";

        // 	$fvalue=htmlentities(mysql_real_unescape_string($phone_code),ENT_QUOTES,"UTF-8");
        echo "<input class='PhonePart PhoneNumber'  type=text  name='phone_{$field_name}'   value='$phone_code' tooltip='Phone Number'>";

        echo "</div>";
    }

    public function renderValueImpl()
    {
        $field_value = $this->field->getValue();
        $pieces = explode("|", $field_value);

        $country_code = "";
        $city_code = "";
        $phone_code = "";
        if (count($pieces) == 3 && strlen($field_value) > 2) {
            echo "+" . $pieces[0] . " " . $pieces[1] . " " . $pieces[2];
        }
        else {
            echo "-";
        }
    }
}

?>