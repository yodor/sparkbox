<?php
include_once("lib/input/renderers/InputField.php");

class HiddenField extends InputField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->attributes["type"] = "hidden";

    }


    public function renderImpl()
    {

        $field_value = $this->input->getValue();

        $field_value = htmlentities(mysql_real_unescape_string($field_value), ENT_QUOTES, "UTF-8");

        $this->attributes["value"] = $field_value;
        $this->attributes["name"] = $this->input->getName();

        $attr = $this->prepareAttributes();

        echo "<input $attr >";
    }

}

?>