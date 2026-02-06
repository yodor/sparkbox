<?php
include_once("beans/DBTableBean.php");

class LanguagesBean extends DBTableBean
{

    protected string $createString = "
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

    /**
     * @return void
     * @throws Exception
     */
    protected function createTable() : void
    {
        parent::createTable();

        try {
            $this->db->transaction();
            $this->db->query("INSERT INTO languages (language, lang_code) values ('" . Spark::Get(Config::DEFAULT_LANGUAGE) . "','" . Spark::Get(Config::DEFAULT_LANGUAGE_ISO3) . "');");
            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}