<?php
include_once("beans/OrderedDataBean.php");

class DynamicPagePhotosBean extends OrderedDataBean
{

    protected string $createString = "CREATE TABLE `page_photos` (
 `ppID` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `photo` longblob NOT NULL,
 `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `caption` text,
 `dpID` int(11) unsigned NOT NULL DEFAULT '0',
 `position` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`ppID`),
 KEY `dpID` (`dpID`),
 CONSTRAINT `page_photos_ibfk_1` FOREIGN KEY (`dpID`) REFERENCES `dynamic_pages` (`dpID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("page_photos");
    }

}