<?php
include_once("lib/beans/DBTableBean.php");


class ProductFeaturesBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `product_features` (
 `pfID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `feature` varchar(255) NOT NULL,
 `prodID` int(11) unsigned NOT NULL,
 PRIMARY KEY (`pfID`),
 KEY `prodID` (`prodID`),
 CONSTRAINT `product_features_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("product_features");
    }

}

?>