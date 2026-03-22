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

    /**
     * Phrase query select - if phrase does not exist / not captured yet - returns zero results
     * Always return 1 or more results if phrase is captured
     * @param int $langID
     * @return SelectQuery
     * @throws Exception
     */
    public function queryPhrase(int $langID) : SelectQuery
    {
        $sel = new SQLSelect();
        $sel->from("site_texts st")->leftJoin("translation_phrases tp")->on("tp.textID=st.textID AND tp.langID=:langID");

        $sel->columns("st.textID", "tp.trID", "tp.langID");
        $sel->column("st.hash_value")->setAlias("hash");
        $sel->column("st.value")->setAlias("phrase");
        $sel->column("tp.translated")->setAlias("translation");

        $sel->bind(":langID", $langID);
//        $sel->setMeta("queryPhrase");

        return new SelectQuery($sel, "textID");
    }


}