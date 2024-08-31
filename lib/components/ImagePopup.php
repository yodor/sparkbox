<?php
include_once("components/Component.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("storage/StorageItem.php");

class ImagePopup extends Component implements IPhotoRenderer
{

    protected string $thumb_url = "";
    protected string $popup_url = "";

    protected int $width = -1;
    protected int $height = 256;

    protected StorageItem $storageItem;

    protected bool $lazyLoad = true;

    protected Component $image;

    public function __construct()
    {
        parent::__construct();
        $this->tagName = "A";

        $this->storageItem = new StorageItem();
        $this->image = new Component();
        $this->image->setClosingTagRequired(false);
        $this->image->setTagName("IMG");
        $this->image->setAttribute("itemprop", "image");
    }

    public function getImage() : Component
    {
        return $this->image;
    }

    public function setLazyLoadEnabled(bool $mode) : void
    {
        $this->lazyLoad = $mode;
        if ($mode) {
            $this->image->setAttribute("loading", "lazy");
        }
        else {
            $this->image->clearAttribute("loading");
        }
    }

    public function isLazyLoadEnabled() : bool
    {
        return $this->lazyLoad;
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

        //if ($this->mode == ImagePopup::MODE_BACKGROUND) {
            $this->setStyleAttribute("background-image", "url({$this->thumb_url})");
        //}

        $this->setAttribute("itemID", $this->storageItem->id);
        $this->setAttribute("itemClass", $this->storageItem->className);
    }

    protected function renderImpl()
    {

        $titleValue = $this->getAttribute("title");
        $alt = $this->image->getAttribute("alt");
        if (!$alt) {
            if ($titleValue) {
                $this->image->setAttribute("alt", $titleValue);
            }
        }

        $this->image->setAttribute("src", $this->thumb_url);
        $this->image->render();

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
