<?php
include_once("components/renderers/items/DataIteratorItem.php");

abstract class NestedSetItem extends DataIteratorItem
{

    public function __construct()
    {
        parent::__construct();
        $this->setComponentClass("Node");

    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->setAttribute("nodeID", $this->id);
        $this->setAttribute("active", (($this->selected) ? 1 : 0));
        $this->setAttribute("checked", (($this->checked) ? 1 : 0));
    }

}

?>
