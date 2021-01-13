<?php
include_once("components/renderers/menus/MenuItemRenderer.php");

class SubmenuItemRenderer extends MenuItemRenderer
{

    public function __construct()
    {
        parent::__construct();
        $this->linkTag->setComponentClass("SubmenuItemLink");
    }

    public function renderSeparator($idx_curr, $items_total)
    {

    }

    public function setMenuItem(MenuItem $item)
    {

        parent::setMenuItem($item);

        if ($item->isSelected()) {
            $this->setAttribute("selected", 1);
        }
        else {
            $this->clearAttribute("selected");
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
