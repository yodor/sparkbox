<?php
include_once("input/renderers/InputFieldTag.php");

class TextField extends InputFieldTag
{

    public function __construct(DataInput $dataInput)
    {
        parent::__construct($dataInput);
        $this->input->setType("text");
    }

}