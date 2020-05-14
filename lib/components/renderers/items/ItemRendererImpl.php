<?php

include_once("components/Component.php");
include_once("components/ItemView.php");

include_once("components/renderers/IItemRenderer.php");

abstract class ItemRendererImpl extends Component implements IItemRenderer
{


    protected $item = NULL;

    public function setItem($item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function __construct()
    {
        parent::__construct();
        $this->attributes["align"] = "left";
        $this->attributes["valign"] = "middle";

        $this->item = NULL;


    }




    //abstract public function renderSeparator($idx_curr, $items_total);

}

?>
