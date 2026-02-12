<?php
include_once("objects/SparkObject.php");
include_once("utils/menu/MenuItemList.php");

class MenuItem extends MenuItemList
{
    protected string $href = "";
    protected string $title = "";

    protected string $icon = "";

    protected int $w = 0;
    protected int $h = 0;

    protected bool $selected = false;
    protected bool $disabled = false;

    protected string $target = "";

    protected bool $need_translate = false;

    public static string $icon_path = "";

    protected string $tooltip = "";

    protected int $id = -1;

    protected string $seoTitle = "";
    protected string $seoDescription = "";

    public function __construct(string $name, string $href = "", string $icon = "", string $tooltip="")
    {
        parent::__construct();

        if (MenuItem::$icon_path) {

        }
        else {
            MenuItem::$icon_path = Spark::Get(Config::SPARK_LOCAL) . "/images/admin/spark_icons/";
        }

        $this->name = $name;
        $this->href = $href;
        $this->icon = $icon;
        $this->target = "";
        $this->need_translate = TRUE;
        $this->selected = false;
        $this->disabled = false;
        $this->tooltip = $tooltip;
        $this->id = -1;
        $this->seoTitle = "";
        $this->seoDescription = "";
    }

    public function setSeoTitle(string $seoTitle) : void
    {
        $this->seoTitle = $seoTitle;
    }
    public function getSeoTitle() : string
    {
        return $this->seoTitle;
    }

    public function setSeoDescription(string $seoDescription): void
    {
        $this->seoDescription = $seoDescription;
    }
    public function getSeoDescription() : string
    {
        return $this->seoDescription;
    }

    public function append(SparkObject $object): void
    {
        parent::append($object);
        $object->setParent($this);
    }

    public function prepend(SparkObject $object): void
    {
        parent::prepend($object);
        $object->setParent($this);
    }

    public function insert(SparkObject $object, int $index): void
    {
        parent::insert($object, $index);
        $object->setParent($this);
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

    /**
     * Set the selection state of this item and all the direct parents to $mode
     * @param bool $mode
     * @return void
     */
    public function setSelected(bool $mode) : void
    {
        $this->selected = $mode;

        $current = $this;
        while ($parent = $current->getParent()) {
            if ($parent instanceof MenuItem) {
                $parent->setSelected($mode);
                $current = $parent;
            }
        }
    }

    public function setTarget(string $target) : void
    {
        $this->target = $target;
    }

    public function getTarget() : string
    {
        return $this->target;
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

    protected function matchItems(Closure $matcher) : ?MenuItem
    {
        //exhaust the submenu items first
        $result = parent::matchItems($matcher);

        //none from submenu
        if (is_null($result)) {
            //try this item
            if ($matcher($this, new URL(urldecode($this->getHref())))) {
                $result = $this;
            }
        }

        return $result;

    }

}