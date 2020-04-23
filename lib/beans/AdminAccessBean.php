<?php
include_once("lib/beans/DBTableBean.php");

class AdminAccessBean extends DBTableBean
{

    protected $createString = "
CREATE TABLE `admin_access` (
 `aclID` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `userID` int(10) unsigned NOT NULL DEFAULT '0',
 `role` varchar(100) NULL,
 PRIMARY KEY (`aclID`),
 KEY `userID` (`userID`),
 CONSTRAINT `admin_access_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `admin_users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
";

    public function __construct()
    {
        parent::__construct("admin_access");
    }


}

?>