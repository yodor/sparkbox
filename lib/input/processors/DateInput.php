<?php
include_once("input/processors/CompoundInput.php");

class DateInput extends CompoundInput
{

    public function __construct(DataInput $input)
    {
        $this->compound_names = array("year", "month", "day");

        parent::__construct($input);
    }


}

?>