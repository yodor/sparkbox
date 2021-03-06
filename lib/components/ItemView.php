<?php
include_once("components/AbstractResultView.php");
include_once("utils/ValueInterleave.php");
include_once("iterators/IDataIterator.php");

class ItemView extends AbstractResultView
{

    public function __construct(IDataIterator $itr)
    {
        parent::__construct($itr);
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ItemView.css";
        return $arr;
    }

    public function startRender()
    {
        parent::startRender();

        echo "<div class='viewport'>";

    }

    public function finishRender()
    {

        echo "</div>";

        parent::finishRender();

    }

    protected function renderImpl()
    {

        if (!$this->item_renderer) throw new Exception("ItemRenderer not set");

        $v = new ValueInterleave("even", "odd");

        $this->position_index = 0;

        while ($row = $this->iterator->next()) {

            $cls = $v->value();

            $this->item_renderer->setID($row[$this->iterator->key()]);
            $this->item_renderer->setData($row);
            $this->item_renderer->render();

            $this->item_renderer->renderSeparator($this->position_index, $this->total_rows);

            $this->position_index++;

            $v->advance();
        }
    }

}

?>