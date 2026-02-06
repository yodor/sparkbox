<?php
include_once("components/AbstractResultView.php");
include_once("utils/ValueInterleave.php");
include_once("iterators/IDataIterator.php");

class ItemView extends AbstractResultView
{

    protected int $items_per_group = 0;
    protected ?Container $group_container = NULL;


    protected Meta $schemaItems;
    protected Meta $schemaName;
    protected Meta $schemaDescription;
    protected Meta $schemaURL;


    public function __construct(?IDataIterator $itr=null)
    {
        parent::__construct($itr);

        $this->setAttribute("itemscope", "");
        $this->setAttribute("itemtype", "https://schema.org/ItemList");
        $this->setAttribute("itemid");

        $this->setTagName("section");

        $this->group_container = new Container(false);
        $this->group_container->setClassName("group");

        $this->schemaItems = new Meta();
        $this->schemaItems->setAttribute("itemprop", "numberOfItems");
        $this->items()->append($this->schemaItems);

        $this->schemaName = new Meta();
        $this->schemaName->setAttribute("itemprop", "name");
        $this->items()->append($this->schemaName);

        $this->schemaDescription = new Meta();
        $this->schemaDescription->setAttribute("itemprop", "description");
        $this->items()->append($this->schemaDescription);

        $this->schemaURL = new Meta();
        $this->schemaURL->setAttribute("itemprop", "url");
        $this->items()->append($this->schemaURL);

        $this->viewport->setTagName("ul");


    }

    public function setSchemaType(string $schemaType) : void
    {
        $this->setAttribute("itemtype", $schemaType);
    }
    public function getSchemaType() : string
    {
        return $this->getAttribute("itemtype");
    }

    public function setName(string $name) : void
    {
        parent::setName($name);
        $this->schemaName->setContent($name);
    }

    public function setSchemaDescription(string $description) : void
    {
        $this->schemaDescription->setContent($description);
    }

    public function getSchemaDescription() : string
    {
        return $this->schemaDescription->getContent();
    }

    public function setSchemaURL(string $schemaURL) : void
    {
        $this->schemaURL->setContent($schemaURL);
    }
    public function getSchemaURL() : string
    {
        return $this->schemaURL->getContent();
    }

    public function processIterator() : void
    {
        parent::processIterator();
        $this->schemaItems->setContent($this->paginator->resultsTotal());
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/ItemView.css";
        return $arr;
    }

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
