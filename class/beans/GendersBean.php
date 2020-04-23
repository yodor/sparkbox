<?php
include_once("lib/beans/DBTableBean.php");


class GendersBean extends DBTableBean
{

    protected $createString = "CREATE TABLE `genders` (
 `gnID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `gender_title` varchar(32) NOT NULL,
 PRIMARY KEY (`gnID`),
 UNIQUE KEY `gender_title` (`gender_title`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("genders");
    }

}

?>