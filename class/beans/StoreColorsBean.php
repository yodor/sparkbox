<?php
include_once ("lib/beans/DBTableBean.php");


class StoreColorsBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `store_colors` (
 `sclrID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `color` varchar(255) NOT NULL,
 `color_code` varchar(10) DEFAULT NULL,
 PRIMARY KEY (`sclrID`),
 UNIQUE KEY `color` (`color`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	parent::__construct("store_colors");
    }

}
?>