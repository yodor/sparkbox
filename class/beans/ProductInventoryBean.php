<?php
include_once("lib/beans/DBTableBean.php");


class ProductInventoryBean extends DBTableBean
{

    protected $createString = "CREATE TABLE `product_inventory` (
 `piID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `prodID` int(11) unsigned NOT NULL,
 `pclrID` int(11) unsigned DEFAULT NULL,
 `size_value` varchar(255) DEFAULT NULL,
 `stock_amount` int(11) NOT NULL DEFAULT '0',
 `price` decimal(10,2) NOT NULL DEFAULT '0.00',
 `buy_price` decimal(10,2) NOT NULL DEFAULT '0.00',
 `old_price` decimal(10,2) NOT NULL DEFAULT '0.00',
 `weight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
 PRIMARY KEY (`piID`),
 KEY `prodID` (`prodID`),
 KEY `pclrID` (`pclrID`),
 KEY `size_value` (`size_value`),
 CONSTRAINT `product_inventory_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `product_inventory_ibfk_3` FOREIGN KEY (`size_value`) REFERENCES `product_sizes` (`size_value`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `product_inventory_ibfk_4` FOREIGN KEY (`pclrID`) REFERENCES `product_colors` (`pclrID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("product_inventory");
    }

}

?>