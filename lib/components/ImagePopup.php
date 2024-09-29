<?php
include_once("components/Action.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("storage/StorageItem.php");
include_once("components/Image.php");

class ImagePopup extends Action
{

    protected Image $image;

    public function __construct()
    {
        parent::__construct();
        $this->setComponentClass("ImagePopup");

        $this->image = new Image();
        $this->image->setStorageItem(new StorageItem());

        $this->items()->append($this->image);

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

    /**
     * Return Image component initialized with StorageItem
     * @return Image
     */
    public function image() : Image
    {
        return $this->image;
    }

    public function setRelation(string $relation) : void
    {
        $this->setAttribute("relation", $relation);
    }

    public function getRelation(): string
    {
        return $this->getAttribute("relation");
    }

    /**
     * Set also the storageItem of the image to $id
     * @param int $id
     * @return void
     */
    public function setID(int $id) : void
    {
        parent::setID($id);
        $this->image->getStorageItem()->id = $id;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        //if ($this->mode == ImagePopup::MODE_BACKGROUND) {
        //    $this->setStyleAttribute("background-image", "url({$this->thumb_url})");
        //}

        $this->setAttribute("itemID", $this->image->getStorageItem()->id);
        $this->setAttribute("itemClass", $this->image->getStorageItem()->className);

        $titleValue = $this->getAttribute("title");
        $alt = $this->image->getAttribute("alt");
        if (!$alt) {
            if ($titleValue) {
                $this->image->setAttribute("alt", $titleValue);
            }
        }

    }

}

?>
