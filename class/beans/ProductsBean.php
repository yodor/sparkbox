<?php
include_once ("lib/beans/DBTableBean.php");

class ProductsBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `products` (
 `prodID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `class_name` varchar(255) DEFAULT NULL,
 `brand_name` varchar(255) NOT NULL,
 `product_code` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
 `product_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
 `product_summary` text NOT NULL,
 `product_description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
 `keywords` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
 `gender` varchar(32) DEFAULT NULL,
 `buy_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
 `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
 `weight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
 `catID` int(11) unsigned NOT NULL,
 `old_price` decimal(10,2) DEFAULT NULL,
 `view_counter` int(11) unsigned NOT NULL DEFAULT '0',
 `order_counter` int(11) unsigned NOT NULL DEFAULT '0',
 `visible` tinyint(1) DEFAULT '0',
 `promotion` tinyint(1) DEFAULT '0',
 `importID` int(11) unsigned DEFAULT NULL,
 `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `insert_date` datetime NOT NULL,
 PRIMARY KEY (`prodID`),
 KEY `catID` (`catID`),
 KEY `importID` (`importID`),
 KEY `gender` (`gender`),
 KEY `brand_name` (`brand_name`),
 KEY `update_date` (`update_date`),
 KEY `insert_date` (`insert_date`),
 KEY `promotion` (`promotion`),
 KEY `visible` (`visible`),
 KEY `class_name` (`class_name`),
 CONSTRAINT `products_ibfk_6` FOREIGN KEY (`class_name`) REFERENCES `product_classes` (`class_name`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `products_ibfk_2` FOREIGN KEY (`gender`) REFERENCES `genders` (`gender_title`),
 CONSTRAINT `products_ibfk_4` FOREIGN KEY (`brand_name`) REFERENCES `brands` (`brand_name`),
 CONSTRAINT `products_ibfk_5` FOREIGN KEY (`catID`) REFERENCES `product_categories` (`catID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8";

    public function __construct() 
    {
	  parent::__construct("products");
    }

}

?>
