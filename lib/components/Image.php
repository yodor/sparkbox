<?php
include_once("components/Component.php");

class Image extends Component implements IPhotoRenderer
{

    protected int $width = -1;
    protected int $height = -1;

    protected bool $use_size_attributes = false;

    protected ?StorageItem $storageItem = null;

    public function __construct()
    {
        parent::__construct();

        $this->setClosingTagRequired(false);
        $this->setTagName("IMG");
        $this->setAttribute("itemprop", "image");

    }

    public function setUseSizeAttributes(bool $mode) : void
    {
        $this->use_size_attributes = $mode;
    }

    public function isUseSizeAttributes() : bool
    {
        return $this->use_size_attributes;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        if ($this->use_size_attributes) {
            if ($this->width > 0) {
                $this->setAttribute("width", $this->width);
            } else {
                $this->removeAttribute("width");
            }
            if ($this->height > 0) {
                $this->setAttribute("height", $this->height);
            } else {
                $this->removeAttribute("height");
            }
        }
        if ($this->storageItem instanceof StorageItem) {
            $this->setAttribute("src", $this->storageItem->hrefImage($this->width, $this->height));
        }
    }

    public function setStorageItem(StorageItem $item) : void
    {
        $this->storageItem = $item;
    }

    public function getStorageItem(): ?StorageItem
    {
        return $this->storageItem;
    }

    /**
     * Set title and alt attributes
     * @param string $text
     * @return void
     */
    public function setTitle(string $text) : void
    {
        parent::setTitle($text);
        $this->setAttribute("alt", $text);
    }

    public function setPhotoSize(int $width, int $height) : void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth() : int
    {
        return $this->width;
    }

    public function getPhotoHeight() : int
    {
        return $this->height;
    }


}
?>