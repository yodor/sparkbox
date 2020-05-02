<?php
include_once("class/pages/ProductsPage.php");


class ProductDetailsPage extends ProductsPage
{

    public function __construct()
    {
        parent::__construct();
        $this->addCSS(SITE_ROOT . "css/product_details.css?ver=1.2");
        $this->addJS(SITE_ROOT . "js/product_details.js?ver=1.2");

    }

}

?>
