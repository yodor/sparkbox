<?php
include_once("components/AbstractResultView.php");
include_once("utils/ValueInterleave.php");
include_once("iterators/IDataIterator.php");

class ItemView extends AbstractResultView
{

    protected $items_per_group = 0;
    protected $group_container = NULL;

    protected $viewport;

    public function __construct(?IDataIterator $itr=null)
    {
        parent::__construct($itr);
        $this->setAttribute("itemscope", "");
        $this->setAttribute("itemtype", "https://schema.org/ItemList");
        $this->group_container = new Container(false);
        $this->group_container->setClassName("group");

        $this->viewport = new Container(true);
        $this->viewport->setComponentClass("viewport");

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
        $this->viewport->startRender();
    }

    public function finishRender()
    {
        $this->viewport->finishRender();
        parent::finishRender();
    }

    public function setItemsPerGroup(int $number)
    {
        $this->items_per_group = $number;
    }

    protected function renderImpl()
    {

        if (!$this->item_renderer) throw new Exception("ItemRenderer not set");

        $url = SparkPage::Instance()->getURL()->fullURL()->toString();

        echo "<link itemprop='url' href='$url'>";
        echo "<meta itemprop='numberOfItems' content='$this->total_rows'>";

        if ($this->getName()) {
            echo "<meta itemprop='name' content='{$this->getName()}'>";
        }

        $this->renderResults();

    }

    protected function renderResults()
    {
        $v = new ValueInterleave();

        $this->position_index = 0;

        $group_listed = 0;

        if ($this->items_per_group>0 && $this->paged_rows<$this->items_per_group) {
            $this->group_container->setAttribute("single", "");
        }

        while ($row = $this->iterator->next()) {

            $cls = $v->value();

            $this->item_renderer->setAttribute("parity", $cls);
            if ($this->items_per_group>0 && $group_listed==0) {
                $this->group_container->startRender();
            }

            $this->item_renderer->setPosition($this->position_index+1);
            if (isset($row[$this->iterator->key()])) {
                $this->item_renderer->setID((int)$row[$this->iterator->key()]);
            }
            $this->item_renderer->setData($row);
            $this->item_renderer->render();

            $this->item_renderer->renderSeparator($this->position_index, $this->total_rows);

            $this->position_index++;
            $group_listed++;

            if ($this->items_per_group>0 && $group_listed==$this->items_per_group) {
                $this->group_container->finishRender();
                $group_listed=0;
            }

            $v->advance();
        }

        if ($this->items_per_group>0 && $group_listed>0 && $group_listed<$this->items_per_group) {
            $this->group_container->finishRender();
        }
    }
}

?>
