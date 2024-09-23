<?php
include_once("beans/DBTableBean.php");
include_once("beans/SiteTextsBean.php");

class TranslationPhrasesBean extends DBTableBean
{

    protected string $createString = "
CREATE TABLE `translation_phrases` (
 `trID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `langID` int(11) unsigned NOT NULL,
 `textID` int(11) unsigned NOT NULL,
 `translated` text COLLATE utf8_unicode_ci NOT NULL,
 PRIMARY KEY (`trID`),
 UNIQUE KEY `langID_2` (`langID`,`textID`),
 KEY `langID` (`langID`),
 KEY `textID` (`textID`),
 CONSTRAINT `translation_phrases_ibfk_1` FOREIGN KEY (`langID`) REFERENCES `languages` (`langID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `translation_phrases_ibfk_2` FOREIGN KEY (`textID`) REFERENCES `site_texts` (`textID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
";

    public function __construct()
    {
        parent::__construct("translation_phrases");
    }

    public function queryLanguageID(int $langID) : SQLQuery
    {
        $sel = new SQLSelect();

        $sel->fields()->set("st.hash_value AS hash", "st.textID", "st.value AS phrase", "tp.translated AS translation");
        $sel->fields()->setExpression(" COALESCE(tp.trID, -1) ", "trID");
        $sel->fields()->setExpression(" COALESCE(tp.langID, $langID) ", "langID");

        $sel->from = " site_texts st LEFT JOIN translation_phrases tp ON tp.textID=st.textID AND tp.langID=$langID ";

        return new SQLQuery($sel, "textID");
    }


}

?>