<?php
include_once("input/renderers/CheckItem.php");

class RadioItem extends CheckItem
{
    public function __construct()
    {
        parent::__construct();
        $this->input->setType("radio");
        $this->items()->removeObject($this->hidden);
        //$this->hidden->setRenderEnabled(false);
    }
}

?>