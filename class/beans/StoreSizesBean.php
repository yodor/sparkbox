<?php
include_once ("lib/beans/DBTableBean.php");


class StoreSizesBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `store_sizes` (
 `pszID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `size_value` varchar(255) NOT NULL,
 PRIMARY KEY (`pszID`),
 UNIQUE KEY `size_value` (`size_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	parent::__construct("store_sizes");
    }

}
?>