<?php
include_once("components/renderers/items/DataIteratorItem.php");

abstract class NestedSetItem extends DataIteratorItem
{

    public function __construct()
    {
        parent::__construct();
        $this->setClassName("Node");

    }

    protected function processAttributes()
    {
        parent::processAttributes(); // TODO: Change the autogenerated stub
        $this->setAttribute("nodeID", $this->id);
        $this->setAttribute("active", (($this->selected) ? 1 : 0));
    }

}

?>