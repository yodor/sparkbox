<?php
include_once ("lib/beans/DBTableBean.php");


class ProductFeaturesBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `product_features` (
 `pfID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `feature` varchar(255) NOT NULL,
 `prodID` int(11) unsigned NOT NULL,
 PRIMARY KEY (`pfID`),
 KEY `prodID` (`prodID`)
) ENGINE=InnoDB AUTO_INCREMENT=2480 DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	parent::__construct("product_features");
    }

}
?>