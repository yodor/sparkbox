<?php
include_once("lib/input/renderers/InputField.php");

abstract class InputFieldTag extends InputField
{

    protected function prepareInputAttributes() : string
    {
        $field_value = mysql_real_unescape_string($this->input->getValue());

        $this->setInputAttribute("value", $field_value);

        return parent::prepareInputAttributes();
    }

    protected function renderImpl()
    {
        $field_attr = $this->prepareInputAttributes();

        echo "<input $field_attr>";
    }


}

?>