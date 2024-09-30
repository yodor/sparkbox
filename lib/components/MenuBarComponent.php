<?php
include_once("components/Component.php");
include_once("utils/menu/MenuItemList.php");
include_once("components/renderers/menus/MenuBarItemRenderer.php");

class MenuBarComponent extends Container
{
    /**
     * @var MenuItemList
     */
    protected MenuItemList $menu;

    /**
     * @var MenuBarItemRenderer
     */
    protected MenuBarItemRenderer $ir_baritem;

    /**
     * @var Component
     */
    protected Component $bar;

    /**
     * @var Component
     */
    protected Component $toggle;

    public bool $toggle_first = true;

    public function __construct(MenuItemList $menu)
    {
        parent::__construct(false);
        $this->setComponentClass("MenuBar");

        $this->setAttribute("itemscope", "");
        $this->setAttribute("itemtype","https://schema.org/SiteNavigationElement");
        $this->setAttribute("role", "menu");

        $this->menu = $menu;
        $this->ir_baritem = new MenuBarItemRenderer();

        if ($this->menu->getName()) {
            $this->setAttribute("menu", $this->menu->getName());
        }

        $this->toggle = new Component(false);
        $this->toggle->setTagName("A");
        $this->toggle->setContents( "<div></div>");
        $this->toggle->setComponentClass("toggle");

        $this->bar = new ClosureComponent($this->renderItems(...), true);
        $this->bar->setComponentClass("MenuBarComponent");

        $this->items->append($this->toggle);
        $this->items->append($this->bar);
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/MenuBarComponent.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/MenuBarComponent.js";
        return $arr;
    }

    public function getMenu(): MenuItemList
    {
        return $this->menu;
    }

    public function setItemRenderer(MenuBarItemRenderer $ir_baritem)
    {
        $this->ir_baritem = $ir_baritem;
    }

    public function getItemRenderer(): MenuBarItemRenderer
    {
        return $this->ir_baritem;
    }

    public function setName(string $name) : void
    {
        parent::setName($name);
        $this->bar->setName($name);
        $this->toggle->setAttribute("title", $name);
    }

    protected function renderItems() : void
    {
        $itemCount = $this->menu->count();

        $iterator = $this->menu->iterator();
        while ($item = $iterator->next()) {
            if (! ($item instanceof MenuItem)) continue;
            $this->ir_baritem->setMenuItem($item);
            $this->ir_baritem->setAttribute("position", $iterator->pos());
            $this->ir_baritem->render();
        }
    }

    public function finishRender()
    {
        parent::finishRender();
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                let menu_bar = new MenuBarComponent();
                menu_bar.attachWith("<?php echo $this->getName();?>");
            });
        </script>
        <?php
    }
}

?>
