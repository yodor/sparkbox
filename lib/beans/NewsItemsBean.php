<?php
include_once("beans/DatedBean.php");

class NewsItemsBean extends DatedBean
{
    protected $createString = "
    CREATE TABLE `news_items` (
    `newsID` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `item_title` varchar(255) NOT NULL,
    `item_date` date NOT NULL,
    `content` text NOT NULL,
    `photo` longblob NOT NULL,
    PRIMARY KEY (`newsID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ";

    public function __construct()
    {
        parent::__construct("news_items");

    }

}

?>