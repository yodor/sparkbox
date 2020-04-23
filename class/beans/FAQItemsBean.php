<?php
include_once("lib/beans/DBTableBean.php");

class FAQItemsBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `faq_items` (
 `fID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `section` enum('General','Orders', 'Returns','Credit Limit','Territories','Shipping','Contact') NOT NULL DEFAULT 'General',
 `question` varchar(255) NOT NULL,
 `answer` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
 PRIMARY KEY (`fID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("faq_items");
    }

}

?>