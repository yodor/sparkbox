<?php
/**
 * Module paths: one normalising (Module::FormatPath) for the keys, the path files and the body class
 */
include_once("templates/Module.php");

test("module paths: keys, path files and body classes from one normalising", function () {
    assertSame("/orders/details", Module::PathKey("/Orders/Details/"));
    assertSame("/home", Module::PathKey("/"));
    assertSame("orders.details", Module::FormatPath("orders/details", ".", false));
    assertSame("orders_details", Module::FormatPath("/orders/details/", "_", false));
    //no '..', digits or other characters reach a path file name
    assertSame("etc.passwd", Module::FormatPath("../etc/passwd2", ".", false));
    assertSame("Orders", Spark::AsciiLetters("Orders-2"));
});
