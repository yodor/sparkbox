<?php
/**
 * URL::Requested() and trailing slashes kept by URL::toString()
 */
include_once("utils/url/URL.php");
include_once("utils/url/PathParameter.php");

test("a path keeps its trailing slash; path parameters are joined without doubling it", function () {
    assertSame("/products/category/1/knigi/", (new URL("/products/category/1/knigi/"))->toString());
    assertSame("/products/category/1/knigi", (new URL("/products/category/1/knigi"))->toString());
    assertSame("/products/", (new URL("/products/"))->toString());

    $url = new URL("/products/");
    $url->add(new PathParameter("prodID", "prodID", false));
    $url->add(new PathParameter("product_name", "product_name", true));
    $url->setData(array("prodID" => 5, "product_name" => "Книга 5"));
    assertTrue(str_starts_with($url->toString(), "/products/5/"), $url->toString());
    assertTrue(str_ends_with($url->toString(), "/"), $url->toString());
});

test("URL::Requested is the address the visitor asked for", function () {
    $_SERVER["REQUEST_URI"] = "/products/5/kniga/?page=2";
    $url = URL::Requested();
    assertSame("/products/5/kniga/", $url->getScriptName());
    assertSame("2", $url->get("page")->value());
});
