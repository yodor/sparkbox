<?php

/**
 * A content that names the addresses of its place for the selection of the main menu (MenuItemList::selectActive):
 * a product or a sub category without its own menu item selects the item of its category or a parent category.
 * The frames with a main menu (StorePage, a site frame) add them after the requested address.
 */
interface IMenuAddresses
{
    /**
     * @return array of URL|string, the preferred first
     */
    public function menuAddresses() : array;
}
