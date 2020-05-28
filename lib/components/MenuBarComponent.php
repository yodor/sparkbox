<?php
include_once("components/Component.php");
include_once("utils/MainMenu.php");
include_once("components/renderers/menus/MenuBarItemRenderer.php");

class MenuBarComponent extends Component
{
    /**
     * @var MainMenu
     */
    protected $main_menu;

    /**
     * @var MenuBarItemRenderer
     */
    protected $ir_baritem;

    /**
     * @var Component
     */
    protected $bar;

    /**
     * @var Component
     */
    protected $toggle;

    public $toggle_first = FALSE;

    public function __construct(MainMenu $menu)
    {
        parent::__construct();

        $this->main_menu = $menu;
        $this->ir_baritem = new MenuBarItemRenderer();

        $bean_name = $menu->getMenuBeanClass();

        if ($bean_name) {

            $this->setAttribute("source", $bean_name);
        }

        $this->bar = new Component();
        $this->bar->setComponentClass("MenuBar");

        $this->toggle = new Component();
        $this->toggle->setTagName("A");

        $this->toggle->setComponentClass("toggle");

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

    public function getMainMenu(): MainMenu
    {
        return $this->main_menu;

    }

    public function setItemRenderer(MenuBarItemRenderer $ir_baritem)
    {
        $this->ir_baritem = $ir_baritem;
    }

    public function getItemRenderer(): MenuBarItemRenderer
    {
        return $this->ir_baritem;
    }

    public function setName(string $name)
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
        $menu_items = $this->main_menu->getMenuItems();

        $total_items = count($menu_items);

        for ($a = 0; $a < $total_items; $a++) {
            $item = $menu_items[$a];

            $this->ir_baritem->setMenuItem($item);
            $this->ir_baritem->render();
            $this->ir_baritem->renderSeparator($a, $total_items);

        }

    }

    public function finishRender()
    {
        parent::finishRender();
        if (!$this->toggle_first) {
            $this->toggle->render();
        }
        $this->bar->finishRender();
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
