<?php
include_once("input/renderers/InputField.php");

//plain <input> tag component
abstract class InputFieldTag extends InputField
{

    protected array $skip_value_types = array("file");

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
    }

    protected function createInput() : Input
    {
        return new Input();
    }

    protected function finalize() : void
    {
        parent::finalize();

        $type = $this->input->getType();

        if (!in_array($type, $this->skip_value_types) ) {
            $this->input->setValue((string)$this->dataInput->getValue());
        }
        else {
            Debug::ErrorLog("Non value attribute input type: " . $type);
        }

    }


}