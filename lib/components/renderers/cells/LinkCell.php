<?php
include_once("components/renderers/cells/TableCell.php");
include_once("components/Action.php");

class LinkCell extends TableCell
{

    public function __construct()
    {
        parent::__construct();
        $this->addClassName("Link");

        $this->action = new Action();

        $this->items()->append($this->action);

    }

    public function setData(array $data) : void
    {
        $value = $this->getContents();
        $this->action->setURL(new URL($value));
        $this->setContents("");
    }


}