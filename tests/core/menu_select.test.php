<?php
/**
 * MenuItemList::selectActive - the item of the page by its address
 */
include_once("utils/url/URL.php");
include_once("utils/menu/MenuItemList.php");

/**
 * Home, Books (category 110) with Novels (category 115) under it, Products, Contacts (old script), News, external
 */
function menuSelectMenu() : MenuItemList
{
    $books = new MenuItem("Books", "/products/category/110/knigi/");
    $books->append(new MenuItem("Novels", "/products/category/115/romani/"));
    $menu = new MenuItemList(array(
        new MenuItem("Home", "/"),
        $books,
        new MenuItem("Products", "/products/"),
        new MenuItem("Contacts", "/contacts.php"),
        new MenuItem("News", "/news/index.php"),
        new MenuItem("Partner", "https://partner.example/products/"),
        new MenuItem("Top", "#top"),
    ));
    return $menu;
}

function menuSelected(MenuItemList $menu, string ...$addresses) : string
{
    $item = $menu->selectActive(...$addresses);
    return ($item instanceof MenuItem) ? $item->getName() : "";
}

test("menu: the exact address, its pages and parameters, a folder for its sub addresses", function () {
    $menu = menuSelectMenu();
    assertSame("Home", menuSelected($menu, "/"));
    assertSame("Home", menuSelected($menu, "/?utm_source=x"), "home with parameters");
    assertSame("Books", menuSelected($menu, "/products/category/110/knigi/"));
    assertSame("Books", menuSelected($menu, "/products/category/110/knigi/?page=2&sort=price"), "page 2 of the category");
    assertSame("Novels", menuSelected($menu, "/products/category/115/romani/"), "the sub item, not its parent");
    assertSame("Products", menuSelected($menu, "/products/"));
    assertSame("Products", menuSelected($menu, "/products/1234/kniga/"), "a product without a category address");
    assertSame("Products", menuSelected($menu, "/products/category/999/drugi/"), "a category without an item");
    assertSame("Contacts", menuSelected($menu, "/contacts.php?sent=1"));
    assertSame("News", menuSelected($menu, "/news/5/zaglavie/"), "index.php is the folder");
    assertSame("News", menuSelected($menu, "/NEWS/"), "letter case");
    assertSame("", menuSelected($menu, "/account/orders"), "no item");
    assertSame("", menuSelected($menu, "/productsx/"), "whole segments only");
});

test("menu: the addresses of the page in order - a product selects the item of its category", function () {
    $menu = menuSelectMenu();
    //the requested address of the product, then its category, then the parent category
    assertSame("Novels", menuSelected($menu, "/products/1234/kniga/", "/products/category/115/romani/", "/products/category/110/knigi/"));
    //a category without an item selects its parent category item
    assertSame("Books", menuSelected($menu, "/products/1234/kniga/", "/products/category/120/poezia/", "/products/category/110/knigi/"));
});

test("menu: an item parameters must be in the address with the same values; encoded and Cyrillic links", function () {
    $menu = new MenuItemList(array(
        new MenuItem("All", "/products/"),
        new MenuItem("Promo", "/products/?promo=1"),
        new MenuItem("Книги", "/products/category/110/" . rawurlencode("книги") . "/"),
    ));
    assertSame("Promo", menuSelected($menu, "/products/?promo=1&page=3"));
    assertSame("All", menuSelected($menu, "/products/?promo=2"));
    assertSame("Книги", menuSelected($menu, "/products/category/110/книги/?page=2"));
});

test("menu: one selection at a time - the parents are selected with the item; the default is the requested address", function () {
    $menu = menuSelectMenu();
    $menu->selectActive("/products/category/115/romani/");
    assertSame(array("Books", "Novels"), array_map(fn(MenuItem $item) => $item->getName(), $menu->getSelectedPath()));

    $_SERVER["REQUEST_URI"] = "/contacts.php";
    assertSame("Contacts", menuSelected($menu));
    assertSame(array("Contacts"), array_map(fn(MenuItem $item) => $item->getName(), $menu->getSelectedPath()), "the previous selection is cleared");
});

test("menu: links to other hosts, '#' and javascript: do not match; this host does", function () {
    $_SERVER["HTTP_HOST"] = "shop.example:8080";
    assertSame(null, MenuItemList::LinkParts("https://partner.example/products/"));
    assertSame(null, MenuItemList::LinkParts("#top"));
    assertSame(null, MenuItemList::LinkParts("javascript:void(0)"));
    assertSame(null, MenuItemList::LinkParts("mailto:a@b.c"));
    assertSame(array(array("products"), array()), MenuItemList::LinkParts("https://shop.example/products/"));
});
