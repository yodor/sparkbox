<?php
include_once("class/pages/ProductsPage.php");


class ProductDetailsPage extends ProductsPage
{

    public function __construct()
    {
        parent::__construct();

    }

    protected function dumpCSS()
    {
        parent::dumpCSS();
        echo "<link rel='stylesheet' href='" . SITE_ROOT . "css/product_details.css?ver=1.2' type='text/css'>";
        echo "\n";
    }

    protected function dumpJS()
    {
        parent::dumpJS();
        echo "<script type='text/javascript' src='" . SITE_ROOT . "js/product_details.js?ver=1.2'></script>";
        echo "\n";
    }


}

?>
