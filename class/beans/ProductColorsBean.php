<?php
include_once ("lib/beans/DBTableBean.php");


class ProductColorsBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `product_colors` (
 `pclrID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `color` varchar(255) NOT NULL,
 `color_photo` longblob COMMENT 'color_chip',
 `prodID` int(11) unsigned NOT NULL,
 PRIMARY KEY (`pclrID`),
 UNIQUE KEY `color_gallery` (`color`,`prodID`),
 KEY `prodID` (`prodID`),
 KEY `color` (`color`),
 CONSTRAINT `product_colors_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `product_colors_ibfk_2` FOREIGN KEY (`color`) REFERENCES `store_colors` (`color`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	parent::__construct("product_colors");
    }

}
?>