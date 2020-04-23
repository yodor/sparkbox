<?php
include_once("lib/input/renderers/InputField.php");

class TextArea extends InputField
{

    public function __construct()
    {
        parent::__construct();

    }

    //expose field attributes as field attributes to calling classes
    public function setAttribute($name, $value)
    {
        $this->setFieldAttribute($name, $value);
    }

    public function renderImpl()
    {
        $field_attrs = $this->prepareFieldAttributes();


        echo "<textarea class='TextArea' $field_attrs>";

        $field_value = $this->field->getValue();
        $field_value = htmlentities(mysql_real_unescape_string($field_value), ENT_QUOTES, "UTF-8");

        echo $field_value;

        echo "</textarea>";
    }

    public function renderValueImpl()
    {
        $field_value = $this->field->getValue();

        if (strlen($field_value) > 0) {
            $field_value = htmlentities(mysql_real_unescape_string($field_value), ENT_QUOTES, "UTF-8");
            $field_value = str_replace("\n", "<BR>", $field_value);
            echo $field_value;
        }
        else {
            echo "-";
        }
    }

}

?>