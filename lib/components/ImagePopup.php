<?php
include_once("components/Component.php");
include_once("components/renderers/IPhotoRenderer.php");

class ImagePopup extends Component implements IPhotoRenderer
{
    public const MODE_IMAGETAG = 1;
    public const MODE_BACKGROUND = 2;

    protected $tagName = "A";
    protected $thumb_url = "";
    protected $popup_url = "";

    protected $width = -1;
    protected $height = 256;

    protected $beanClass = "";
    protected $id = -1;

    protected $mode = ImagePopup::MODE_IMAGETAG;

    public function __construct()
    {
        parent::__construct();

    }

    public function setRenderMode(int $mode)
    {
        $this->mode = $mode;
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ImagePopup.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/ImagePopup.js";
        return $arr;
    }

    public function setID(int $id)
    {
        $this->id = $id;
    }

    public function setBeanClass(string $beanClass)
    {
        $this->beanClass = $beanClass;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->beanClass = get_class($bean);
    }

    protected function processAttributes()
    {
        parent::processAttributes();
        $this->thumb_url = StorageItem::Image($this->id, $this->beanClass, $this->width, $this->height);
        $this->popup_url = StorageItem::Image($this->id, $this->beanClass);

        $this->setAttribute("href", $this->popup_url);
        if ($this->mode == ImagePopup::MODE_BACKGROUND) {

            $this->setStyleAttribute("background-image", "url({$this->thumb_url})");
        }
    }

    protected function renderImpl()
    {
        if ($this->mode == ImagePopup::MODE_IMAGETAG) {
            echo "<img src='{$this->thumb_url}'>";
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