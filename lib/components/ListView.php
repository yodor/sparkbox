<?php
include_once("pages/HTMLPage.php");
include_once("components/AbstractResultView.php");
include_once("components/renderers/items/TextItemRenderer.php");
include_once("components/renderers/IItemRenderer.php");

include_once("components/PageResultsPanel.php");

class ListView extends AbstractResultView
{

    protected $item_renderer;

    public function __construct(IDataIterator $itr)
    {
        parent::__construct($itr);
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ListView.css";
        return $arr;
    }

    public function setItemRenderer(IItemRenderer $renderer)
    {
        $this->item_renderer = $renderer;
        $this->item_renderer->setParent($this);
    }

    public function getItemRenderer()
    {
        return $this->item_renderer;
    }

    public function startRender()
    {
        parent::startRender();

        echo "<div class='viewport'>";

        if (!$this->paginators_enabled) {
            if (strlen($this->caption) > 0) {
                echo "<div class='caption'>";
                echo $this->caption;
                echo "</div>";
            }
        }

    }

    public function finishRender()
    {
        echo "<div class=clear></div>";
        echo "</div>";

        parent::finishRender();
    }


    public function renderImpl()
    {

        if (!$this->item_renderer) throw new Exception("ItemRenderer not set");

        $v = new ValueInterleave("even", "odd");

        $this->position_index = 0;

        while ($row = $this->itr->next()) {

            $cls = $v->value();

            //$this->item_renderer->setClassName($cls);

            $this->item_renderer->setItem($row);
            $this->item_renderer->render();
            $this->item_renderer->renderSeparator($this->position_index, $this->total_rows);

            $this->position_index++;

            $v->advance();
        }
    }

    public function getIterator() : IDataIterator
    {
        return $this->itr;
    }

}

?>
