<?php
include_once("components/Container.php");
include_once("components/ClosureComponent.php");
include_once("components/InlineScript.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("components/renderers/items/DataIteratorItem.php");
include_once("components/renderers/IDataIteratorRenderer.php");

class GalleryTapeItem extends DataIteratorItem implements IPhotoRenderer
{

    /**
     * @var ImagePopup
     */
    protected ImagePopup $image_popup;

    public function __construct()
    {
        parent::__construct();

        $this->setComponentClass("slot");

        $this->image_popup = new ImagePopup();
        $this->image_popup->image()->setPhotoSize(-1, 128);

        $this->items()->append($this->image_popup);
    }

    public function setDataID(int $id) : void
    {
        parent::setDataID($id);
        $this->image_popup->setDataID($id);
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->image_popup->setData($data);
    }

    public function setItemURL(URL $url) : void
    {
        $this->image_popup->setURL($url);
    }

    /**
     * Set the ImagePopup bean class
     * @param string $beanClass
     */
    public function setBeanClass(string $beanClass) : void
    {
        $this->image_popup->image()->getStorageItem()->className = $beanClass;
    }

    /**
     * Sets the component name and set the ImagePopup relation attribute
     * @param string $name
     */
    public function setName(string $name) : void
    {
        //parent::setName($name);

        $this->image_popup->setRelation($name);
    }

    public function setPhotoSize(int $width, int $height): void
    {
        $this->image_popup->image()->setPhotoSize($width, $height);
    }

    public function getPhotoWidth(): int
    {
        return $this->image_popup->image()->getPhotoWidth();
    }

    public function getPhotoHeight(): int
    {
        return $this->image_popup->image()->getPhotoHeight();
    }

}

/**
 * Horizontal tape of item renderers (one per iterator row) with left / right buttons (GalleryTape.js)
 *
 * contents
 *   button left
 *   viewport
 *     slots - the item renderer once per row
 *   button right
 */
class GalleryTape extends Container implements IDataIteratorRenderer
{

    /**
     * @var IDataIterator
     */
    protected IDataIterator $iterator;

    protected DataIteratorItem $item_renderer;

    protected Container $contents;

    public function __construct()
    {
        parent::__construct();

        $this->contents = new Container(false);
        $this->contents->setComponentClass("contents");

        $left = new Component(false);
        $left->setComponentClass("button left");
        $this->contents->items()->append($left);

        $viewport = new Container(false);
        $viewport->setComponentClass("viewport");
        $slots = new ClosureComponent($this->renderSlots(...), true, false);
        $slots->setComponentClass("slots");
        $viewport->items()->append($slots);
        $this->contents->items()->append($viewport);

        $right = new Component(false);
        $right->setComponentClass("button right");
        $this->contents->items()->append($right);

        $this->items()->append($this->contents);
    }

    public function getCacheName() : string
    {

        if (!($this->iterator instanceof SelectQuery)) return "";

        return parent::getCacheName()."-".$this->iterator->getCacheName();

    }

    public function requiredStyle(): array
    {
        $ret = parent::requiredStyle();
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/css/GalleryTape.css";
        return $ret;
    }

    public function requiredScript(): array
    {
        $ret = parent::requiredScript();
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/js/SwipeListener.js";
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/js/GalleryTape.js";
        return $ret;
    }

    /**
     * Sets the component name and set the ImagePopup relation attribute
     * @param string $name
     * @return void
     * @throws Exception
     */
    public function setName(string $name) : void
    {
        parent::setName($name);

        if (!$this->item_renderer) throw new Exception("Item renderer not set");
        $this->item_renderer->setName($name);
    }

    public function setIterator(IDataIterator $query): void
    {
        $this->iterator = $query;
    }

    public function getIterator(): IDataIterator
    {
        return $this->iterator;
    }

    public function setItemRenderer(DataIteratorItem $item): void
    {
        $this->item_renderer = $item;
    }

    public function getItemRenderer(): ?DataIteratorItem
    {
        return $this->item_renderer;
    }

    public function startRender(): void
    {
        parent::startRender();

        $this->iterator->exec();
    }

    /**
     * The item renderer once per iterator row, with its position
     * @return void
     */
    protected function renderSlots() : void
    {
        $pos = 0;
        while ($data = $this->iterator->next()) {
            $this->item_renderer->setAttribute("position", $pos);
            $this->item_renderer->setDataID($data[$this->iterator->key()]);
            $this->item_renderer->setData($data);
            $this->item_renderer->render();
            $pos++;
        }
    }

    /**
     * The tape script is rendered after the (cacheable) tape
     * @return void
     */
    public function render(): void
    {
        parent::render();

        $script = new InlineScript(true);
        $name = json_encode($this->getName());
        $script->setCode(<<<JS
        let gallery_tape = new GalleryTape();
        gallery_tape.setName($name);
        gallery_tape.initialize();
JS);
        $script->render();
    }

}