<?php
include_once("beans/DBTableBean.php");

class BeanTranslationsBean extends DBTableBean
{

    protected $createString = "
CREATE TABLE `translation_beans` (
 `btID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `table_name` varchar(50) CHARACTER SET utf8 NOT NULL,
 `field_name` varchar(50) CHARACTER SET utf8 NOT NULL,
 `bean_id` int(11) unsigned NOT NULL,
 `translated` text COLLATE utf8_unicode_ci NOT NULL,
 `langID` int(11) unsigned NOT NULL,
 PRIMARY KEY (`btID`),
 UNIQUE KEY `table_name` (`table_name`,`field_name`,`langID`,`bean_id`),
 KEY `langID` (`langID`),
 CONSTRAINT `translation_beans_ibfk_1` FOREIGN KEY (`langID`) REFERENCES `languages` (`langID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
";

    public function __construct()
    {
        parent::__construct("translation_beans");
    }

    public function queryForBean()
    {

    }

}

?>