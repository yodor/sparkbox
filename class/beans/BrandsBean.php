<?php
include_once("lib/beans/DBTableBean.php");


class BrandsBean extends DBTableBean
{

    protected $createString = "CREATE TABLE `brands` (
 `brandID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `brand_name` varchar(255) NOT NULL,
 `summary` text NOT NULL,
 `url` varchar(255) DEFAULT NULL,
 `photo` longblob,
 PRIMARY KEY (`brandID`),
 UNIQUE KEY `brand_name` (`brand_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("brands");
    }

}

?>