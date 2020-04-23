<?php
include_once("lib/beans/DBTableBean.php");

class ClassAttributeValuesBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `class_attribute_values` (
 `cavID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `prodID` int(11) unsigned NOT NULL,
 `caID` int(11) unsigned NOT NULL,
 `value` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
 PRIMARY KEY (`cavID`),
 UNIQUE KEY `prodID_2` (`prodID`,`caID`),
 KEY `prodID` (`prodID`),
 KEY `caID` (`caID`),
 CONSTRAINT `class_attribute_values_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `class_attribute_values_ibfk_2` FOREIGN KEY (`caID`) REFERENCES `class_attributes` (`caID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("class_attribute_values");
    }

}

?>
