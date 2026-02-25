<?php
include_once("components/Component.php");
include_once("utils/menu/MenuItemList.php");
include_once("components/renderers/menus/MenuListRenderer.php");

class MenuBarInitScript extends PageScript
{

    public function code() : string
    {
        return <<<JS
        onPageLoad(function(){
            const menu_bar = new MenuBar();
            menu_bar.setID("{$this->getName()}");
            menu_bar.initialize();
        });
JS;
    }

}

class MenuBar extends Container
{
    /**
     * @var MenuItemList
     */
    protected ?MenuItemList $menu = null;
    /**
     * @var MenuListRenderer
     */
    protected MenuListRenderer $bar;

    /**
     * @var Component
     */
    protected Component $toggle;

    protected MenuBarInitScript $initScript;

    public function __construct(?MenuItemList $menu=null)
    {
        parent::__construct(false);
        $this->setComponentClass("MenuBar");
        $this->setTagName("nav");

        $this->setAttribute("itemscope");
        $this->setAttribute("itemtype","https://schema.org/SiteNavigationElement");
        $this->setAttribute("role", "menu");

        $this->toggle = new Component(false);
        $this->toggle->setTagName("SPAN");
        $this->toggle->setContents( "<div></div>");
        $this->toggle->setComponentClass("toggle");

        $this->bar = new MenuListRenderer();
        $this->bar->setComponentClass("ItemList");

        $this->items->append($this->toggle);
        $this->items->append($this->bar);

        $this->initScript = new MenuBarInitScript();

        if ($menu) {
            $this->setMenu($menu);
        }
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/MenuBar.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/MenuBar.js";
        return $arr;
    }

    public function getMenu(): ?MenuItemList
    {
        return $this->menu;
    }
    public function setMenu(MenuItemList $itemList) : void
    {
        $this->menu = $itemList;
        $this->bar->setItemList($itemList);

        if ($this->menu->getName()) {
            $this->setAttribute("id", $this->menu->getName());
            $this->initScript->setName($this->menu->getName());
        }
    }

    public function getBar() : MenuListRenderer
    {
        return $this->bar;
    }

    public function disableSubmenus() : void
    {

    }


}