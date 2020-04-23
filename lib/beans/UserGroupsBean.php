<?php
include_once("lib/beans/DBTableBean.php");

class UserGroupsBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `user_groups` (
 `gmID` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `groupID` int(10) unsigned NOT NULL,
 `userID` int(10) unsigned NOT NULL,
 `assign_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`gmID`),
 UNIQUE KEY `groupID` (`groupID`,`userID`),
 KEY `userID` (`userID`),
 CONSTRAINT `user_groups_ibfk_1` FOREIGN KEY (`groupID`) REFERENCES `groups` (`groupID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `user_groups_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("user_groups");
    }

    public function isMemberOf($userID, $groupID)
    {
        $userID = (int)$userID;
        $groupID = (int)$groupID;
        $n = $this->startIterator(" WHERE userID='$userID' AND groupID='$groupID' ");
        if ($n > 0) return true;
        return false;
    }

}