<?php
include_once ("lib/beans/DBTableBean.php");

class ClassAttributesBean extends DBTableBean
{

    protected $createString = "CREATE TABLE `class_attributes` (
 `caID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `catID` int(11) unsigned NOT NULL,
 `maID` int(11) unsigned NOT NULL,
 `default_value` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
 PRIMARY KEY (`caID`),
 UNIQUE KEY `catID_2` (`catID`,`maID`),
 KEY `catID` (`catID`),
 KEY `maID` (`maID`),
 CONSTRAINT `class_attributes_ibfk_1` FOREIGN KEY (`catID`) REFERENCES `product_categories` (`catID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `class_attributes_ibfk_3` FOREIGN KEY (`catID`) REFERENCES `product_categories` (`catID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `class_attributes_ibfk_6` FOREIGN KEY (`maID`) REFERENCES `attributes` (`maID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	parent::__construct("class_attributes");
    }

}

?>