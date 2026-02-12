<?php
include_once("beans/DBTableBean.php");

class AdminAccessBean extends DBTableBean
{

    protected string $createString = "
CREATE TABLE `admin_access` (
 `aclID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `userID` int(11) unsigned NOT NULL,
 `role` varchar(255) NOT NULL DEFAULT '',
 PRIMARY KEY (`aclID`),
 UNIQUE KEY `userID_2` (`userID`,`role`),
 KEY `userID` (`userID`),
 KEY `role` (`role`),
 CONSTRAINT `admin_access_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `admin_users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
";

    public function __construct()
    {
        parent::__construct("admin_access");
    }

}