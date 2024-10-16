<?php
include_once("input/renderers/InputField.php");

//plain <input> tag component
abstract class InputFieldTag extends InputField
{

    protected $skip_value_types = array("file");

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
    }

    protected function createInput() : Input
    {
        return new Input();
    }

    protected function processAttributes() : void
    {
        parent::processAttributes();

        $type = $this->input->getType();

        if (!in_array($type, $this->skip_value_types) ) {
//            $dataValue = attributeValue();
            $this->input->setValue((string)$this->dataInput->getValue());
        }
        else {
            debug("Non value attribute input type: " . $type);
        }

    }


}

?>
