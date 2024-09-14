<?php
include_once("components/renderers/menus/MenuItemRenderer.php");

class SubmenuItemRenderer extends MenuItemRenderer
{

    public function __construct()
    {
        parent::__construct();
        $this->linkTag->setComponentClass("SubmenuItemLink");
    }

    public function renderSeparator(int $idx_curr, int $items_total) : void
    {

    }

    public function setMenuItem(MenuItem $item) : void
    {

        parent::setMenuItem($item);

        if ($item->isSelected()) {
            $this->setAttribute("selected", 1);
        }
        else {
            $this->removeAttribute("selected");
        }
    }

    public function renderImpl()
    {

        echo "<div class='SubmenuItemOuter'>";

        $this->linkTag->render();

        echo "</div>"; //SubmenuItemOuter

    }

}

?>
