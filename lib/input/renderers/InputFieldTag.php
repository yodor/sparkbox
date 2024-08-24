<?php
include_once("input/renderers/InputField.php");

//plain <input> tag component
abstract class InputFieldTag extends InputField
{

    protected $skip_value_types = array("file");

    protected function processInputAttributes()
    {
        parent::processInputAttributes();

        $process_value = true;

        if ($this->haveInputAttribute("type")) {

            $type = $this->getInputAttribute("type");

            if (in_array($type, $this->skip_value_types)) {
                $process_value = false;
                debug("Disable value setting for type: ".$type);
            }

        }

        if ($process_value) {
            $field_value = mysql_real_unescape_string($this->input->getValue());
            $this->setInputAttribute("value", $field_value);
        }


    }

    protected function renderImpl()
    {
        $field_attr = $this->prepareInputAttributes();
        echo "<input $field_attr>";
    }

}

?>
