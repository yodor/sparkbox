<?php
include_once("lib/beans/OrderedDataBean.php");


class ProductPhotosBean extends OrderedDataBean
{
    protected $createString = "CREATE TABLE `product_photos` (
 `ppID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `photo` longblob NOT NULL,
 `prodID` int(11) unsigned NOT NULL,
 `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `caption` varchar(255) DEFAULT NULL,
 `position` int(11) NOT NULL DEFAULT '0',
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