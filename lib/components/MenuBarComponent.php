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

    public bool $toggle_first = FALSE;

    protected bool $separator_enabled = true;

    public function __construct(MenuItemList $menu)
    {
        parent::__construct(false);

        $this->menu = $menu;
        $this->ir_baritem = new MenuBarItemRenderer();

        if ($this->menu->getName()) {
            $this->setAttribute("menu", $this->menu->getName());
        }

        $this->bar = new Component(false);
        $this->bar->setComponentClass("MenuBar");

        $this->bar->setAttribute("itemscope", "");
        $this->bar->setAttribute("itemtype","https://schema.org/SiteNavigationElement");
        $this->bar->setAttribute("role", "menu");

        $this->toggle = new Component(false);
        $this->toggle->setTagName("A");
        $this->toggle->setContents( "<div></div>");
        $this->toggle->setComponentClass("toggle");

    }

    public function setToggleFirst() : void
    {
        $this->toggle_first = true;
    }

    public function setToggleLast() : void
    {
        $this->toggle_first = false;
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

    public function setSeparatorEnabled(bool $mode)
    {
        $this->separator_enabled = $mode;
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

    public function startRender()
    {
        $this->bar->startRender();
        if ($this->toggle_first) {
            $this->toggle->render();
        }
        parent::startRender();

    }

    protected function renderImpl()
    {

        $itemCount = $this->menu->count();

        $iterator = $this->menu->iterator();
        while ($item = $iterator->next()) {
            if (! ($item instanceof MenuItem)) continue;
            $this->ir_baritem->setMenuItem($item);
            $this->ir_baritem->setAttribute("position", $iterator->pos());
            $this->ir_baritem->render();
            if ($this->separator_enabled) {
                $this->ir_baritem->renderSeparator($iterator->pos(), $itemCount);
            }
        }
    }

    public function render()
    {
        parent::render();
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                let menu_bar = new MenuBarComponent();
                menu_bar.attachWith("<?php echo $this->getName();?>");
                menu_bar.showSelected();
            });
        </script>
        <?php
    }

    public function finishRender()
    {
        parent::finishRender();
        if (!$this->toggle_first) {
            $this->toggle->render();
        }
        $this->bar->finishRender();
    }
}

?>
