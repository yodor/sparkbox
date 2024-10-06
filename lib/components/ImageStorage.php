<?php
include_once("components/Action.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("storage/StorageItem.php");
include_once("components/Image.php");

/**
 * Backend storage image reference
 */
class ImageStorage extends Action
{

    protected Image $image;

    public function __construct(?StorageItem $storageItem = null)
    {
        parent::__construct();
        $this->setComponentClass("ImageStorage");
        //force DIV
        $this->setTagName("DIV");

        $this->image = new Image();

        if (is_null($storageItem)) {
            $this->image->setStorageItem(new StorageItem());
        }
        else {
            $this->image->setStorageItem($storageItem);
        }

        $this->items()->append($this->image);

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
     * ImagePopup is part of collection of items tagged with relation attributed named $relation
     * @param string $relation
     * @return void
     */
    public function setListRelation(string $relation) : void
    {
        $this->setAttribute("list-relation", $relation);
    }

    public function getListRelation(): string
    {
        return $this->getAttribute("list-relation");
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

        $this->setAttribute("itemID", $this->image->getStorageItem()->id);
        $this->setAttribute("itemClass", $this->image->getStorageItem()->className);

    }

}

?>