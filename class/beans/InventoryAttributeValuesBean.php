<?php
include_once ("lib/beans/DBTableBean.php");

class InventoryAttributeValuesBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `inventory_attribute_values` (
 `cavID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `piID` int(11) unsigned NOT NULL,
 `caID` int(11) unsigned NOT NULL,
 `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
 PRIMARY KEY (`cavID`),
 UNIQUE KEY `inventory_attributes` (`piID`,`caID`),
 KEY `caID` (`caID`),
 KEY `piID` (`piID`),
 CONSTRAINT `inventory_attribute_values_ibfk_2` FOREIGN KEY (`caID`) REFERENCES `class_attributes` (`caID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `inventory_attribute_values_ibfk_1` FOREIGN KEY (`piID`) REFERENCES `product_inventory` (`piID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	  parent::__construct("inventory_attribute_values");
    }

}

?>
