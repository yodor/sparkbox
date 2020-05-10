<?php

include_once("components/Component.php");
include_once("components/renderers/IItemRenderer.php");

class TextItemRenderer extends Component implements IItemRenderer
{

    private $field_name;
    protected $item;

    public function setItem($item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function __construct($field_name)
    {
        parent::__construct();
        $this->setAttribute("align", "left");
        $this->setAttribute("valign", "middle");
        $this->setFieldName($field_name);
    }

    public function setFieldName($field_name)
    {
        $this->field_name = $field_name;
    }

    public function startRender()
    {
        $all_attr = $this->prepareAttributes();
        echo "<div $all_attr>";
    }

    public function finishRender()
    {
        echo "</div>";
    }

    public function renderImpl()
    {
        echo $this->item[$this->field_name];
    }

    public function renderSeparator($idx_curr, $items_total)
    {

    }
}

?>