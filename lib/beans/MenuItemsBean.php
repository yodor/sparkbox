<?php
include_once("lib/beans/NestedSetBean.php");

class MenuItemsBean extends NestedSetBean
{
	protected $createString = "CREATE TABLE `menu_items` (
 `menuID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `menu_title` varchar(50) NOT NULL,
 `link` varchar(255) NOT NULL,
 `parentID` int(11) unsigned NOT NULL DEFAULT '0',
 `lft` int(11) unsigned NOT NULL,
 `rgt` int(11) unsigned NOT NULL,
 PRIMARY KEY (`menuID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

	public function __construct()
	{
		parent::__construct("menu_items");
	}

}

?>