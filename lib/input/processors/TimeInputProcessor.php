<?php
include_once("input/processors/CompoundInputProcessor.php");

class TimeInputProcessor extends CompoundInputProcessor
{

    public function __construct()
    {
        $this->compound_names = array("hour", "minute");
        $this->concat_char = ":";
        parent::__construct();

    }


}

?>