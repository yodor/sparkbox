<?php
include_once("utils/MainMenu.php");

class MenuItem
{
    protected $href = "";
    protected $title = "";
    protected $icon = "";

    protected $w = 0;
    protected $h = 0;

    protected $selected = FALSE;
    protected $disabled = FALSE;

    protected $parent_item = NULL;

    protected $target = "";

    protected $childNodes = array();

    protected $need_translate = TRUE;

    public static $icon_path = SPARK_LOCAL . "/images/admin/spark_icons/";

    public function __construct($title, $href = "", $icon = "")
    {
        $this->title = $title;
        $this->href = $href;
        $this->icon = $icon;
        $this->need_translate = TRUE;
    }
    //flag for renderers to handle the title translation themselves - enableTranslation(true) - default - uses tr($title)
    //enableTranslation(false) -  title is already translated translation in MainMenu::constructMenuItems 
    public function enableTranslation(bool $mode)
    {
        $this->need_translate = $mode;
    }

    public function needTranslate()
    {
        return $this->need_translate;
    }

    public function setDisabled($mode)
    {
        $this->disabled = (($mode > 0) ? TRUE : FALSE);
    }

    public function isDisabled()
    {
        return $this->disabled;
    }

    public function isSelected()
    {
        return $this->selected;
    }

    public function setSelected($mode)
    {
        $this->selected = (($mode > 0) ? TRUE : FALSE);
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function getTarget()
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

    public function getParent()
    {
        return $this->parent_item;
    }

    public function getSubmenu()
    {
        return $this->childNodes;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getHref()
    {
        return $this->href;
    }

    public function setHref($href)
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

    public function setSize($width, $height)
    {
        $this->w = $width;
        $this->h = $height;
    }
}

?>