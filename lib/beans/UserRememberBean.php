<?php
include_once("lib/beans/DBTableBean.php");

class UserRememberBean extends DBTableBean
{
    protected $createString = "
CREATE TABLE `user_remember` (
 `urID` int(10) unsigned NOT NULL auto_increment,
 `userID` int(10) unsigned NOT NULL,
 `remember_cookie` varchar(32) NOT NULL,
 `remember_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
 PRIMARY KEY  (`urID`),
 UNIQUE KEY `userID` (`userID`),
 CONSTRAINT `user_remember_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

    public function __construct()
    {
        parent::__construct("user_remember");
    }

}

?>