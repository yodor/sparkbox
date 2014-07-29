<?php
include_once ("lib/beans/DBTableBean.php");


class ProductPhotosBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `product_photos` (
 `ppID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `photo` longblob NOT NULL,
 `prodID` int(11) unsigned NOT NULL,
 PRIMARY KEY (`ppID`),
 KEY `prodID` (`prodID`),
 CONSTRAINT `product_photos_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	parent::__construct("product_photos");
    }

}
?>