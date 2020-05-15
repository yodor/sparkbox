<?php
include_once("components/renderers/IRenderer.php");
include_once("utils/MenuItem.php");

interface IMenuItemRenderer extends IRenderer
{
    public function setMenuItem(MenuItem $item);

    public function getMenuItem();

    public function renderSeparator($idx_curr, $items_total);

}

?>