<?php
include_once("beans/DBTableBean.php");

class FAQSectionsBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `faq_sections` (
 `fqsID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `section_name` varchar(255) NOT NULL,
 PRIMARY KEY (`fqsID`),
 UNIQUE KEY `section_name` (`section_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("faq_sections");
    }
}