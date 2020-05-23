<?php
include_once("beans/DBTableBean.php");

class FAQItemsBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `faq_items` (
 `fID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `fqsID` int(11) unsigned NOT NULL,
 `question` varchar(255) NOT NULL,
 `answer` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
 PRIMARY KEY (`fID`),
 KEY `fqsID` (`fqsID`),
 CONSTRAINT `faq_items_ibfk_1` FOREIGN KEY (`fqsID`) REFERENCES `faq_sections` (`fqsID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("faq_items");
    }

}