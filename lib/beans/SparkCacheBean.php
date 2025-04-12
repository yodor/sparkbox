<?php
include_once("beans/DBTableBean.php");

class SparkCacheBean extends DBTableBean
{
    protected string $createString = "CREATE TABLE `sparkcache` (
  `entryID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cacheName` varchar(512) NOT NULL,
  `className` varchar(512) NOT NULL,
  `beanID` int(11) unsigned NOT NULL,
  `data` longblob NOT NULL,
  `lastModified` int(11) unsigned NOT NULL,
  PRIMARY KEY (`entryID`),
  UNIQUE KEY `cacheName` (`cacheName`,`className`,`beanID`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
";

    public function __construct()
    {
        parent::__construct("sparkcache");
    }
}
?>