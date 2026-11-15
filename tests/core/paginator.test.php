<?php
/**
 * Paginator: the URL counts pages from 1, currentPage() from 0
 */
include_once("utils/Paginator.php");

test("?page=2 is the second page (index 1), ?page=1 and none are the first, nonsense is the first", function () {
    foreach (array("2" => 1, "1" => 0, "0" => 0, "-3" => 0, "x" => 0) as $value => $index) {
        $_GET[Paginator::KEY_PAGE] = $value;
        assertSame($index, (new Paginator())->currentPage(), "page=$value");
    }
    unset($_GET[Paginator::KEY_PAGE]);
    assertSame(0, (new Paginator())->currentPage(), "no page");
});

test("SetPage: the first page has no parameter, the others index + 1", function () {
    $url = new URL("/products/category/2/x/?page=5&orderby=price");
    Paginator::SetPage($url, 0);
    assertSame("/products/category/2/x/?orderby=price", $url->toString());
    Paginator::SetPage($url, 1);
    assertSame("2", $url->get(Paginator::KEY_PAGE)->value());
});
