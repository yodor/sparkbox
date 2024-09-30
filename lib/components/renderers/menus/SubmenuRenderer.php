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

    public function setMenuItem(MenuItem $item) : void
    {
        $this->menu_item = $item;

    }

    protected function renderImpl()
    {
        //clone
        $menu_item = clone $this->menu_item;

        $iterator = $menu_item->iterator();
        while ($curr = $iterator->next()) {
            if (! ($curr instanceof MenuItem)) continue;

            $this->ir_menuitem->setMenuItem($curr);

            $this->ir_menuitem->startRender();

            $this->ir_menuitem->renderImpl();

            if ($curr->count() > 0) {
                $this->setMenuItem($curr);
                $this->render();
            }

            $this->ir_menuitem->finishRender();
        }

    }

}

?>
