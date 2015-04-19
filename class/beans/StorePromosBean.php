<?php
include_once ("lib/beans/DBTableBean.php");


class StorePromosBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `store_promos` (
 `spID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `start_date` date NOT NULL,
 `end_date` date NOT NULL,
 `target` enum('Product','Category') NOT NULL,
 `targetID` int(11) unsigned NOT NULL,
 `discount_percent` int(11) NOT NULL,
 PRIMARY KEY (`spID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	parent::__construct("store_promos");
    }

}
?>