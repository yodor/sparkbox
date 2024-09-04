<?php
include_once("components/renderers/menus/MenuItemRenderer.php");
include_once("components/renderers/menus/SubmenuRenderer.php");

class MenuBarItemRenderer extends MenuItemRenderer
{

    protected $ir_submenu = NULL;

    public function __construct()
    {
        parent::__construct();

        $this->ir_submenu = new SubmenuRenderer();

    }

    public function setSubmenuRenderer(IMenuItemRenderer $ir_submenu)
    {
        $this->ir_submenu = $ir_submenu;
    }

    public function disableSubmenuRenderer()
    {
        $this->ir_submenu = NULL;
    }

    public function setMenuItem(MenuItem $item)
    {

        parent::setMenuItem($item);

        $submenu = $item->getSubmenu();

        if (count($submenu) > 0) {
            $this->setAttribute("have_submenu", "1");
        }
        else {
            $this->removeAttribute("have_submenu");
        }

        if ($item->isSelected()) {
            $this->setAttribute("active", "1");
        }
        else {
            $this->removeAttribute("active");
        }

    }

    protected function renderImpl()
    {

        echo "<div class='MenuItemOuter'>";

        $this->linkTag->render();

        echo "</div>";

        if (count($this->item->getSubmenu()) > 0) {
            if ($this->ir_submenu) {

                $this->ir_submenu->setMenuItem($this->item);
                $this->ir_submenu->render();

            }

        }

    }

}

?>
