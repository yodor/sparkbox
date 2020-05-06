<?php
include_once("input/processors/CompoundInputProcessor.php");

class DateInputProcessor extends CompoundInputProcessor
{

    public function __construct()
    {
        $this->compound_names = array("year", "month", "day");

        parent::__construct();
    }


}

?>