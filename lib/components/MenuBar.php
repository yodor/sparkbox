<?php
include_once("components/Component.php");
include_once("utils/menu/MenuItemList.php");
include_once("components/renderers/menus/MenuListRenderer.php");
include_once("components/InlineScript.php");

class MenuBarInitScript extends InlineScript implements IPageComponent
{

    protected function finalize() : void
    {
        $this->enableOnPageLoad();
        //force single instance inside page_components - same init for all menus as IPageComponent uses class and name for the key
        $this->setName("");
        $code = <<<JS
document.querySelectorAll(".MenuBar").forEach((node) => {
    const menuBar = new MenuBar();
    menuBar.initializeNode(node);
    menuBar.initialize();
});
JS;
        $this->setCode($code);
        parent::finalize();
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
    }

    public function getBar() : MenuListRenderer
    {
        return $this->bar;
    }

    public function disableSubmenus() : void
    {

    }

}