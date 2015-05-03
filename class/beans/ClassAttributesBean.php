<?php
include_once ("lib/beans/DBTableBean.php");

class ClassAttributesBean extends DBTableBean
{

    protected $createString = "CREATE TABLE `class_attributes` (
 `caID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `pclsID` int(11) unsigned NOT NULL,
 `class_name` varchar(255) NOT NULL,
 `attribute_name` varchar(255) NOT NULL,
 `default_value` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
 PRIMARY KEY (`caID`),
 UNIQUE KEY `class_attributes` (`class_name`,`attribute_name`),
 KEY `attribute_name` (`attribute_name`),
 KEY `class_name` (`class_name`),
 KEY `pclsID` (`pclsID`),
 CONSTRAINT `class_attributes_ibfk_9` FOREIGN KEY (`pclsID`) REFERENCES `product_classes` (`pclsID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `class_attributes_ibfk_7` FOREIGN KEY (`attribute_name`) REFERENCES `attributes` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `class_attributes_ibfk_8` FOREIGN KEY (`class_name`) REFERENCES `product_classes` (`class_name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	parent::__construct("class_attributes");
    }

}

?>