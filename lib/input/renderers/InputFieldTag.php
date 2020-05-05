<?php
include_once("lib/input/renderers/InputField.php");

abstract class InputFieldTag extends InputField
{

    public function startRender()
    {

        $field_value = mysql_real_unescape_string($this->input->getValue());

        $this->setFieldAttribute("value", $field_value);
        $this->setFieldAttribute("name", $this->input->getName());


        parent::startRender();

    }

    protected function renderImpl()
    {
        $field_attr = $this->prepareFieldAttributes();

        echo "<input $field_attr>";
    }


}

?>