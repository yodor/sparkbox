<?php
include_once("beans/DBTableBean.php");

class GroupsBean extends DBTableBean
{
    // CREATE TABLE IF NOT EXISTS groups (
    //   `groupID` int(10) unsigned NOT NULL auto_increment,
    //   `group_name` varchar(100) NOT NULL,
    //   PRIMARY KEY  (`groupID`)
    // ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

    public function __construct()
    {
        parent::__construct("groups");
    }

}