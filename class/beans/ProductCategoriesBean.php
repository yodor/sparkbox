<?php
include_once("lib/beans/NestedSetBean.php");


class ProductCategoriesBean extends NestedSetBean
{

    protected $createString = "CREATE TABLE `product_categories` (
    `catID` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `category_name` varchar(50) NOT NULL,
    `parentID` int(11) unsigned NOT NULL DEFAULT '0',
    `lft` int(11) unsigned NOT NULL,
    `rgt` int(11) unsigned NOT NULL,
    PRIMARY KEY (`catID`),
    KEY `category_name` (`category_name`),
    KEY `parentID` (`parentID`),
    KEY `lft` (`lft`),
    KEY `rgt` (`rgt`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("product_categories");
    }

}

?>