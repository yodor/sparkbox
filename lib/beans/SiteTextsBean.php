<?php
include_once("beans/DBTableBean.php");

class SiteTextsBean extends DBTableBean
{

    protected $createString = "
CREATE TABLE `site_texts` (
 `textID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `value` text COLLATE utf8_unicode_ci NOT NULL,
 `hash_value` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
 PRIMARY KEY (`textID`),
 UNIQUE KEY `hash_value` (`hash_value`)
) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci
";

    public function __construct()
    {
        parent::__construct("site_texts");
    }


}