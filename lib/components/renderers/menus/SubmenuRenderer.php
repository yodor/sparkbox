<?php
include_once("components/Component.php");
include_once("components/renderers/menus/SubmenuItemRenderer.php");
include_once("components/renderers/IMenuItemRenderer.php");

class SubmenuRenderer extends Component implements IMenuItemRenderer
{

    protected ?MenuItem $menu_item = NULL;

    protected ?SubmenuItemRenderer $ir_menuitem = NULL;


    public function __construct()
    {
        parent::__construct(false);
        $this->ir_menuitem = new SubmenuItemRenderer();

    }

    public function getMenuItem() : ?MenuItem
    {
        return $this->menu_item;
    }

    public function renderSeparator($idx_curr, $items_total) : void
    {
        if ($idx_curr < $items_total - 1) {
            echo "\n<div class='SubmenuSeparator' position='$idx_curr'><div></div></div>";
        }
    }

    public function setMenuItem(MenuItem $item) : void
    {
        $this->menu_item = $item;

    }

    protected function renderImpl()
    {
        //clone
        $menu_item = clone $this->menu_item;

        $submenu = $menu_item->getSubmenu();

        $items_count = count($submenu);

        for ($a = 0; $a < $items_count; $a++) {
            $curr = $submenu[$a];

            $this->ir_menuitem->setMenuItem($curr);

            $this->ir_menuitem->startRender();

            $this->ir_menuitem->renderImpl();

            if (count($curr->getSubmenu()) > 0) {
                $this->setMenuItem($curr);
                $this->render();
            }

            $this->ir_menuitem->finishRender();

        }
    }

}

?>
