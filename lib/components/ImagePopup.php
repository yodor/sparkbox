<?php
include_once("components/Component.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("storage/StorageItem.php");

class ImagePopup extends Component implements IPhotoRenderer
{
    public const MODE_IMAGETAG = 1;
    public const MODE_BACKGROUND = 2;

    protected $tagName = "A";
    protected $thumb_url = "";
    protected $popup_url = "";

    protected $width = -1;
    protected $height = 256;

    protected $mode = ImagePopup::MODE_IMAGETAG;

    protected $storageItem;

    public function __construct()
    {
        parent::__construct();
        $this->storageItem = new StorageItem();

    }

    public function setRelation(string $relation)
    {
        $this->setAttribute("relation", $relation);
    }

    public function getRelation(): ?string
    {
        return $this->getAttribute("relation");
    }

    public function setStorageItem(StorageItem $storageItem)
    {
        $this->storageItem = $storageItem;
    }

    public function getStorageItem() : StorageItem
    {
        return $this->storageItem;
    }

    public function setRenderMode(int $mode)
    {
        $this->mode = $mode;
    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ImagePopup.css";
        return $arr;
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/ImagePopup.js";
        return $arr;
    }

    public function setID(int $id)
    {
        $this->storageItem->id = $id;
    }

    public function getID(): int
    {
        return $this->storageItem->id;
    }

    public function setBeanClass(string $beanClass)
    {
        $this->storageItem->className = $beanClass;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->storageItem->className = get_class($bean);
    }

    public function getBeanClass(): string
    {
        return $this->storageItem->className;
    }

    protected function processAttributes()
    {
        parent::processAttributes();
        $this->thumb_url = $this->storageItem->hrefImage($this->width, $this->height);
        $this->popup_url = $this->storageItem->hrefImage();

        if ($this->mode == ImagePopup::MODE_BACKGROUND) {
            $this->setStyleAttribute("background-image", "url({$this->thumb_url})");
        }

        $this->setAttribute("itemID", $this->storageItem->id);
        $this->setAttribute("itemClass", $this->storageItem->className);
    }

    protected function renderImpl()
    {
        if ($this->mode == ImagePopup::MODE_IMAGETAG) {
            $alt_attr = "";
            $titleValue = $this->getAttribute("title");
            if ($titleValue) {
                $alt_attr = "alt='".attributeValue($titleValue)."'";
            }

            echo "<img itemprop='image' loading='lazy' src='{$this->thumb_url}' $alt_attr>";
        }

    }

    public function setPhotoSize(int $width, int $height)
    {
        // TODO: Implement setPhotoSize() method.
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth(): int
    {
        return $this->width;
    }

    public function getPhotoHeight(): int
    {
        return $this->height;
    }
}

?>