<?php
include_once("lib/components/Component.php");
include_once("lib/utils/MainMenu.php");
include_once("lib/components/renderers/menus/MenuBarItemRenderer.php");
include_once("lib/components/MLTagComponent.php");

class MenuBarComponent extends Component
{
    protected $main_menu;
    protected $ir_baritem;
    protected $bar;
    public $toggle_first = false;

    public function __construct(MainMenu $menu)
    {
        parent::__construct();

        $this->main_menu = $menu;
        $this->ir_baritem = new MenuBarItemRenderer();

        $bean_name = $menu->getMenuBeanClass();

        if ($bean_name) {

            $this->setAttribute("source", $bean_name);
        }

        $this->bar = new MLTagComponent("DIV");
        $this->bar->setClassName("MenuBar");

        $this->toggle = new MLTagComponent("A");
        $this->toggle->setClassName("toggle");


    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/MenuBarComponent.css";
        return $arr;

    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SITE_ROOT . "lib/js/MenuBarComponent.js";
        return $arr;
    }

    public function getMainMenu()
    {
        return $this->main_menu;

    }

    public function setItemRenderer(MenuBarItemRenderer $ir_baritem)
    {
        $this->ir_baritem = $ir_baritem;
    }

    public function getItemRenderer()
    {
        return $this->ir_baritem;
    }

    public function setName($name)
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

    public function renderImpl()
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
            addLoadEvent(function () {
                let menu_bar = new MenuBarComponent();
                menu_bar.attachWith("<?php echo $this->getName();?>");

            });
        </script>
        <?php
    }
}

?>
