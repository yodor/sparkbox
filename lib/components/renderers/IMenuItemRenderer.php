<?php
include_once("utils/menu/MenuItem.php");

interface IMenuItemRenderer
{
    public function setMenuItem(MenuItem $item) : void;

    public function getMenuItem() : ?MenuItem;

}

?>
