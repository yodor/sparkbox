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

    protected static function CreateTable(string $createText) : void
    {
        parent::CreateTable($createText);

        $query = new DBQuery();
        try {
            $insert = new SQLInsert();
            $insert->from = "languages";
            $insert->set("language", Spark::Get(Config::DEFAULT_LANGUAGE));
            $insert->set("lang_code", Spark::Get(Config::DEFAULT_LANGUAGE_ISO3));

            $query->exec($insert);
        }
        catch (Exception $ex) {
            Debug::ErrorLog("Unable to insert default language: ".$ex->getMessage());
            throw $ex;
        }
        finally {
            $query->free();
        }

    }
}