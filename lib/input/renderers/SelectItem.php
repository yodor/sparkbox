<?php
include_once("components/renderers/items/DataIteratorItem.php");

class SelectItem extends DataIteratorItem
{
    public function __construct()
    {
        parent::__construct(false);
        //options are not styled
        $this->setComponentClass("");

        $this->tagName = "OPTION";
    }

    public function processAttributes(): void
    {
        parent::processAttributes();

        //no name for options
        $this->removeAttribute("name");

        if ($this->isSelected()) {
            $this->setAttribute("SELECTED", "");
        }
        else {
            $this->removeAttribute("SELECTED");
        }

        $this->setAttribute("value", attributeValue((string)$this->value));
        //allow html tags here
        $this->setContents($this->label);
    }

}

?>