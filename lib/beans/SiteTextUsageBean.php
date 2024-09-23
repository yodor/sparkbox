<?php
include_once("beans/DBTableBean.php");

class SiteTextUsageBean extends DBTableBean
{
    protected string $createString = "
CREATE TABLE `site_text_usage` (
 `stuID` int(10) unsigned NOT NULL auto_increment,
 `textID` int(10) unsigned NOT NULL,
 `usedby` varchar(128) collate utf8_unicode_ci NOT NULL,
 `capture_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
 PRIMARY KEY  (`stuID`),
 UNIQUE KEY `textID_2` (`textID`,`usedby`),
 KEY `textID` (`textID`),
 KEY `usedby` (`usedby`),
 CONSTRAINT `site_text_usage_ibfk_1` FOREIGN KEY (`textID`) REFERENCES `site_texts` (`textID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
";

    public function __construct()
    {
        parent::__construct("site_text_usage");
    }

}