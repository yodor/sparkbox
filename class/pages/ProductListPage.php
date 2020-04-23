<?php
include_once("class/pages/ProductsPage.php");
include_once("class/utils/ProductsQuery.php");

class ProductListPage extends ProductsPage
{


    public function __construct()
    {
        parent::__construct();

    }

    protected function dumpCSS()
    {
        parent::dumpCSS();
        echo "<link rel='stylesheet' href='" . SITE_ROOT . "css/related_tree.css?ver=1.0' type='text/css'>";
        echo "\n";
    }

    protected function dumpJS()
    {
        parent::dumpJS();
        echo "<script type='text/javascript' src='" . SITE_ROOT . "js/product_list.js?ver=1.2'></script>";
        echo "\n";
    }


}

?>
