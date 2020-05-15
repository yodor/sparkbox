<?php
include_once("input/processors/CompoundInput.php");

class TimeInput extends CompoundInput
{

    public function __construct(DataInput $input)
    {
        $this->compound_names = array("hour", "minute");
        $this->concat_char = ":";
        parent::__construct($input);

    }

}

?>