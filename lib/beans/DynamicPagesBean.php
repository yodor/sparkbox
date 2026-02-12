<?php
include_once("beans/OrderedDataBean.php");

class DynamicPagesBean extends OrderedDataBean
{
    protected string $createString = "CREATE TABLE `dynamic_pages` (
  `dpID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_title` varchar(255) NOT NULL DEFAULT '',
  `item_date` date DEFAULT '0000-00-00',
  `visible` tinyint(1) DEFAULT 0,
  `subtitle` text DEFAULT NULL,
  `content` text NOT NULL,
  `keywords` text NOT NULL DEFAULT '',
  `position` int(11) unsigned DEFAULT NULL,
  `photo` longblob DEFAULT NULL,
  PRIMARY KEY (`dpID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8
";

    public function __construct()
    {
        parent::__construct("dynamic_pages");
    }

}