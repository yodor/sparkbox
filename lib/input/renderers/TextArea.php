<?php
include_once("lib/input/renderers/InputField.php");

class TextArea extends InputField
{

    public function renderImpl()
    {
        $field_attrs = $this->prepareInputAttributes();

        echo "<textarea $field_attrs>";

        echo htmlentities(mysql_real_unescape_string($this->input->getValue()), ENT_QUOTES, "UTF-8");

        echo "</textarea>";
    }


}

?>