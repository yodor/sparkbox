<?php
include_once("components/ImageStorage.php");
include_once("pages/HTMLHead.php");

class ImagePopup extends ImageStorage
{
    /**
     * The texts of ImagePopup.js (the labels of its buttons)
     */
    const array SCRIPT_TEXTS = array("Zoom", "Close", "Previous photo", "Next photo");

    public function __construct(?StorageItem $storageItem = null)
    {
        parent::__construct($storageItem);
        $this->setComponentClass("ImagePopup");
        //force A
        $this->setTagName("a");
        //the buttons of the popup (ImagePopup.js)
        HTMLHead::Instance()->addTexts(...ImagePopup::SCRIPT_TEXTS);
    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/ImagePopup.css";
        return $arr;
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/ImagePopup.js";
        return $arr;
    }

    protected function syncAttrs(): void
    {
        parent::syncAttrs();
        $titleValue = $this->getAttribute("title");
        $alt = $this->image->getAttribute("alt");
        if (!$alt && $titleValue) {
            $this->image->setAttribute("alt", $titleValue);
        }
    }
}