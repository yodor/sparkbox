<?php
include_once("components/renderers/items/DataIteratorItem.php");

class ListItem extends DataIteratorItem
{
    protected Meta $positionMeta;

    public function __construct()
    {
        parent::__construct();

        $this->setAttribute("itemscope");
        $this->setAttribute("itemtype", "https://schema.org/ListItem");
        $this->setAttribute("itemprop","itemListElement");

        $this->positionMeta = new Meta();
        $this->positionMeta->setAttribute("itemprop","position");
        $this->items()->append($this->positionMeta);
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        //start positioning from 1 as per recommendation
        $this->positionMeta->setContent(($this->position+1));
    }
}