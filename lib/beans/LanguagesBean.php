<?php
include_once("lib/beans/DBTableBean.php");

class LanguagesBean extends DBTableBean
{

    protected $createString = "
CREATE TABLE `languages` (
 `langID` int(10) unsigned NOT NULL auto_increment,
 `language` varchar(50) collate utf8_unicode_ci NOT NULL,
 `lang_code` varchar(3) collate utf8_unicode_ci NOT NULL,
 PRIMARY KEY  (`langID`),
 UNIQUE KEY `language` (`language`),
 UNIQUE KEY `lang_code` (`lang_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
";

    public function __construct()
    {
        parent::__construct("languages");
    }

    public function id4language($lng_name)
    {

        $db = $this->db;

        $lng_name = $db->escapeString($lng_name);

        $q = "SELECT langID from {$this->table} WHERE language='$lng_name'";
        $res = $db->query($q);
        $row = $db->fetch($res);
        if (!$row) {
            throw new Exception ("No such language: $lng_name");
        }
        return $row["langID"];
    }

    protected function createTable()
    {
        parent::createTable();
        $db = $this->db;
        $db->transaction();
        $db->query("INSERT INTO languages (language, lang_code) values ('" . DEFAULT_LANGUAGE . "','" . DEFAULT_LANGUAGE_ISO3 . "');");
        $db->commit();
    }
}