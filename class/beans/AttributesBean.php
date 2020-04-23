<?php
include_once("lib/beans/DBTableBean.php");


class AttributesBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `attributes` (
 `maID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 `unit` varchar(255) DEFAULT NULL,
 `type` int(11) DEFAULT NULL,
 PRIMARY KEY (`maID`),
 UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("attributes");
    }

}

?>