<?php
include_once("lib/input/renderers/InputField.php");

abstract class InputFieldTag extends InputField
{

    public function __construct()
    {
        parent::__construct();

    }

    public function renderField(DataInput $field)
    {

        $field_value = mysql_real_unescape_string($field->getValue());

        $this->setFieldAttribute("value", $field_value);
        $this->setFieldAttribute("name", $field->getName());


        parent::renderField($field);

    }

    public function renderImpl()
    {
        $field_attr = $this->prepareFieldAttributes();

        echo "<input $field_attr>";

    }


}

?>