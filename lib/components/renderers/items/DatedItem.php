<?php
include_once("utils/IDataResultConsumer.php");
include_once("components/renderers/items/DataIteratorItem.php");
include_once("beans/DatedBean.php");

class DatedItem extends DataIteratorItem implements IDataResultConsumer
{

    protected ?DatedBean $bean = null;

    protected ?Component $title = null;
    protected ?Component $date = null;
    protected ?Component $content = null;
    protected ?ImageStorage $thumbnail = null;

    public function  __construct(DatedBean $bean)
    {
        parent::__construct();
        $this->bean = $bean;

        $this->setTagName("div");
        $this->setComponentClass("DatedItem");
        $this->addClassName(get_class($this->bean));

        $this->title = new Component(false);
        $this->title->setComponentClass("title");

        $this->date = new Component(false);
        $this->date->setComponentClass("date");

        $this->thumbnail = new ImageStorage();
        $this->thumbnail->setComponentClass("thumbnail");
        $this->thumbnail->image()->getStorageItem()->className = get_class($bean);
//        $this->thumbnail->image()->setPhotoSize(64,64);

        $this->content = new Component(false);
        $this->content->setComponentClass("content");

        $this->items()->append($this->title);
        $this->items()->append($this->date);
        $this->items()->append($this->thumbnail);
        $this->items()->append($this->content);

        //value is id
        $this->setValueKey($this->bean->key());
        $this->setLabelKey("item_title");

    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $this->title->setContents($this->label);

        $this->date->setContents($this->bean->formatDate(strtotime($data["item_date"])));

//        $this->thumbnail->setData($data);
        $this->thumbnail->setID($this->id);

        $this->content->setContents($data["content"]);
    }

    public function collectDataKeys(): array
    {
        return ["item_title", "item_date", "content"];
    }
}