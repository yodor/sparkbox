<?php
include_once("utils/menu/MainMenu.php");
include_once("objects/SparkObject.php");

class MenuItem extends SparkObject
{
    protected string $href = "";
    protected string $title = "";

    protected string $icon = "";

    protected int $w = 0;
    protected int $h = 0;

    protected bool $selected = false;
    protected bool $disabled = false;

    protected string $target = "";

    protected array $childNodes = array();

    protected bool $need_translate = false;

    public static string $icon_path = SPARK_LOCAL . "/images/admin/spark_icons/";

    protected string $tooltip = "";

    protected int $id = -1;

    public function __construct(string $name, string $href = "", string $icon = "", string $tooltip="")
    {
        parent::__construct();

        $this->name = $name;
        $this->href = $href;
        $this->icon = $icon;
        $this->target = "";
        $this->need_translate = TRUE;
        $this->childNodes = array();
        $this->selected = false;
        $this->disabled = false;
        $this->tooltip = $tooltip;
        $this->id = -1;
    }

    public function setID(int $id) : void
    {
        $this->id = $id;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getTooltip() : string
    {
        return $this->tooltip;
    }

    public function setTooltip(string $text) : void
    {
        $this->tooltip = $text;
    }

    //flag for renderers to handle the title translation themselves - enableTranslation(true) - default - uses tr($title)
    //enableTranslation(false) -  title is already translated translation in MainMenu::constructMenuItems 
    public function enableTranslation(bool $mode) : void
    {
        $this->need_translate = $mode;
    }

    public function needTranslate() : bool
    {
        return $this->need_translate;
    }

    public function setDisabled(bool $mode) : void
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

    public function setSelected(bool $mode) : void
    {
        $this->selected = $mode;
    }

    public function setTarget(string $target) : void
    {
        $this->target = $target;
    }

    public function getTarget() : string
    {
        return $this->target;
    }

    public function clearSubmenu() : void
    {
        $this->childNodes = array();
    }

    public function addMenuItem(MenuItem $m) : void
    {
        $this->childNodes[] = $m;
        $m->setParent($this);

    }

    public function getSubmenu() : array
    {
        return $this->childNodes;
    }

    public function getHref() : string
    {
        return $this->href;
    }

    public function setHref(string $href) : void
    {
        $this->href = $href;
    }

    public function getIcon() : string
    {
        return $this->icon;
    }

    public function getWidth() : int
    {
        return $this->w;
    }

    public function getHeight() : int
    {
        return $this->h;
    }

    public function setSize(int $width, int $height) : void
    {
        $this->w = $width;
        $this->h = $height;
    }
}

?>