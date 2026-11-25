<?php
include_once("beans/DBTableBean.php");
include_once("utils/Translator.php");

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
     * The new table has English (the language of the code) and the language of the site (the language of
     * Config::DEFAULT_LOCALE, its English name: 'bg' - 'bulgarian') - two-letter codes
     */
    protected static function CreateTable(string $createText) : void
    {
        parent::CreateTable($createText);

        $rows = array("en" => "english");
        $code = Translator::LocaleLanguage((string)Spark::Get(Config::DEFAULT_LOCALE));
        if ($code !== "en") {
            //the English name of the code ('bg' - 'bulgarian')
            $name = strtolower((string)Locale::getDisplayLanguage($code, "en"));
            $rows[$code] = ($name !== "") ? $name : $code;
        }

        $query = new DBQuery();
        try {
            foreach ($rows as $langCode => $language) {
                $insert = SQLInsert::Table("languages");
                $insert->set("language", $language);
                $insert->set("lang_code", $langCode);
                $query->exec($insert);
            }
        }
        catch (Exception $ex) {
            Debug::ErrorLog("Unable to insert the languages: ".$ex->getMessage());
            throw $ex;
        }
        finally {
            $query->free();
        }
    }
}