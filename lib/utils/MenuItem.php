<?php
include_once("utils/MainMenu.php");

class MenuItem
{
    protected $href;
    protected $title;
    protected $icon;

    protected $w = 0;
    protected $h = 0;

    protected $selected;
    protected $disabled;

    protected $parent_item;

    protected $target;

    protected $childNodes;

    protected $need_translate;

    public static $icon_path = SPARK_LOCAL . "/images/admin/spark_icons/";

    public function __construct(string $title, string $href = "", string $icon = "")
    {
        $this->title = $title;
        $this->href = $href;
        $this->icon = $icon;
        $this->target = "";
        $this->need_translate = TRUE;
        $this->childNodes = array();
        $this->selected = false;
        $this->disabled = false;
        $this->parent_item = NULL;

    }
    //flag for renderers to handle the title translation themselves - enableTranslation(true) - default - uses tr($title)
    //enableTranslation(false) -  title is already translated translation in MainMenu::constructMenuItems 
    public function enableTranslation(bool $mode)
    {
        $this->need_translate = $mode;
    }

    public function needTranslate() : bool
    {
        return $this->need_translate;
    }

    public function setDisabled(bool $mode)
    {
        $this->disabled = $mode;
    }

    public function isDisabled() : bool
    {
        return $this->disabled;
    }

    public function isSelected() : bool
    {
        return $this->selected;
    }

    public function setSelected(bool $mode)
    {
        $this->selected = $mode;
    }

    public function setTarget(string $target)
    {
        $this->target = $target;
    }

    public function getTarget() : string
    {
        return $this->target;
    }

    public function clearChildNodes()
    {
        $this->childNodes = array();
    }

    public function addMenuItem(MenuItem $m)
    {
        $this->childNodes[] = $m;
        $m->setParent($this);

    }

    public function setParent(MenuItem $m)
    {
        $this->parent_item = $m;
    }

    public function getParent() : ?MenuItem
    {
        return $this->parent_item;
    }

    public function getSubmenu() : array
    {
        return $this->childNodes;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function getHref() : string
    {
        return $this->href;
    }

    public function setHref(string $href)
    {
        $this->href = $href;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getWidth()
    {
        return $this->w;
    }

    public function getHeight()
    {
        return $this->h;
    }

    public function setSize(int $width, int $height)
    {
        $this->w = $width;
        $this->h = $height;
    }
}

?>