<?php
include_once("lib/beans/OrderedDataBean.php");

class DynamicPagesBean extends OrderedDataBean
{
  protected $createString = "CREATE TABLE `dynamic_pages` (
 `dpID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `item_title` varchar(255) NOT NULL DEFAULT '',
 `item_date` date DEFAULT '0000-00-00',
 `visible` tinyint(1) DEFAULT '0',
 `subtitle` text,
 `content` text NOT NULL,
 `position` int(11) unsigned DEFAULT NULL,
 `photo` longblob NOT NULL,
 PRIMARY KEY (`dpID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";



	public function __construct()
	{
		parent::__construct("dynamic_pages");
	}
	
}

?>