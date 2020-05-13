<?php
include_once("input/processors/InputProcessor.php");

class PhoneInput extends CompoundInput
{

    public function __construct(DataInput $input)
    {
        $this->compound_names = array("country", "city", "phone");
        $this->concat_char = "|";
        parent::__construct($input);

    }

}

?>