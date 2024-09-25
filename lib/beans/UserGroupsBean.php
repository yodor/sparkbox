<?php
include_once("beans/DBTableBean.php");

class UserGroupsBean extends DBTableBean
{
    protected string $createString = "CREATE TABLE `user_groups` (
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

    public function isMemberOf(int $userID, int $groupID) : bool
    {
        $qry = $this->query();
        $qry->select->where()->add("userID", $userID)->add("groupID", $groupID);
        $qry->select->fields()->set("userID");
        $qry->select->limit = " 1 ";
        if ($qry->exec() > 0) return TRUE;
        return FALSE;
    }

}