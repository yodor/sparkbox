<?php
include_once("input/renderers/InputField.php");

class TextArea extends InputField
{

    public function __construct(DataInput $dataInput)
    {
        parent::__construct($dataInput);
    }

    protected function createInput() : Input
    {
        $input = new Input();
        $input->setTagName("textarea");
        $input->setClosingTagRequired(true);

        return $input;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        $dataValue = Spark::AttributeValue((string)$this->dataInput->getValue());

        $this->input->setContents($dataValue);
    }

}