<?php
include_once("components/AbstractResultView.php");
include_once("utils/ValueInterleave.php");
include_once("iterators/IDataIterator.php");

class ItemView extends AbstractResultView
{

    protected int $items_per_group = 0;
    protected ?Container $group_container = NULL;

    protected Link $schemaURL;
    protected Meta $schemaItems;
    protected Meta $schemaName;


    public function __construct(?IDataIterator $itr=null)
    {
        parent::__construct($itr);
        $this->setAttribute("itemscope", "");
        $this->setAttribute("itemtype", "https://schema.org/ItemList");
        $this->group_container = new Container(false);
        $this->group_container->setClassName("group");

        $url = URL::Current()->fullURL()->toString();
        $this->schemaURL = new Link();
        $this->schemaURL->setHref($url);
        $this->schemaURL->setAttribute("itemprop", "url");
        $this->viewport->items()->append($this->schemaURL);

        $this->schemaItems = new Meta();
        $this->schemaItems->setAttribute("itemprop", "numberOfItems");
        $this->viewport->items()->append($this->schemaItems);

        $this->schemaName = new Meta();
        $this->schemaName->setAttribute("itemprop", "name");
        $this->viewport->items()->append($this->schemaName);


    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ItemView.css";
        return $arr;
    }

//    public function startRender()
//    {
//        parent::startRender();
//        $this->viewport->startRender();
//    }
//
//    public function finishRender()
//    {
//        $this->viewport->finishRender();
//        parent::finishRender();
//    }

    public function setItemsPerGroup(int $number): void
    {
        $this->items_per_group = $number;
    }


    protected function renderItem(RawResult $result) : void
    {
        static $v = new ValueInterleave();

        $cls = $v->value();

        $id = $this->iterator->key();

        $this->item_renderer->setAttribute("parity", $cls);

        $this->item_renderer->setPosition($this->position_index);

        if ($result->isSet($id)) {
            $this->item_renderer->setID((int)$result->get($id));
        }
        $this->item_renderer->setData($result->toArray());
        $this->item_renderer->render();

        $v->advance();
    }
}

?>
