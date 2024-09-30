<?php
include_once("components/ImageStorage.php");

class ImagePopup extends ImageStorage
{

    public function __construct(?StorageItem $storageItem = null)
    {
        parent::__construct($storageItem);
        $this->addClassName("ImagePopup");
        //force A
        $this->setTagName("A");
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

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $titleValue = $this->getAttribute("title");
        $alt = $this->image->getAttribute("alt");
        if (!$alt && $titleValue) {
            $this->image->setAttribute("alt", $titleValue);
        }
    }
}

?>
