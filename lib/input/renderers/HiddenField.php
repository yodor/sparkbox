<?php
include_once("input/renderers/InputFieldTag.php");

class HiddenField extends InputFieldTag
{

    public function __construct(DataInput $dataInput)
    {
        parent::__construct($dataInput);
        $this->input->setType("hidden");
    }

}

?>