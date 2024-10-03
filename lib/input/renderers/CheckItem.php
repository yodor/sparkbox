<?php
include_once("input/renderers/RadioItem.php");

class CheckItem extends RadioItem
{
    public function __construct()
    {
        parent::__construct();
        $this->setClassName("CheckItem");

        $this->input->setType("checkbox");
    }
}

?>