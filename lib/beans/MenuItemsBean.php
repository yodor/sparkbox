<?php
include_once("beans/NestedSetBean.php");

class MenuItemsBean extends NestedSetBean
{
    protected string $createString = "CREATE TABLE `menu_items` (
  `menuID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `menu_title` varchar(50) NOT NULL,
  `seo_title` varchar(512) DEFAULT NULL,
  `seo_description` varchar(512) DEFAULT NULL,
  `link` varchar(255) NOT NULL,
  `parentID` int(11) unsigned NOT NULL DEFAULT 0,
  `lft` int(11) unsigned NOT NULL,
  `rgt` int(11) unsigned NOT NULL,
  PRIMARY KEY (`menuID`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
";

    public function __construct($table_name = "menu_items")
    {
        parent::__construct($table_name);
    }

}