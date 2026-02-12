<?php
include_once("components/renderers/items/DataIteratorItem.php");

class TextItem extends DataIteratorItem
{

    public function __construct()
    {
        parent::__construct();
        $this->setClassName("TextItem");
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->setContents($this->value);
    }

}