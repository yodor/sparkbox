<?php
include_once("input/renderers/InputFieldTag.php");

class DateField extends InputFieldTag
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->input->setType("date");
    }


}