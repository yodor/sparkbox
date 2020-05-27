<?php
include_once("beans/DBTableBean.php");
include_once("beans/SiteTextsBean.php");

class TranslationPhrasesBean extends DBTableBean
{

    protected $createString = "
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

    public function queryLanguageID(int $langID)
    {
        $sel = new SQLSelect();

        $sel->fields()->set("st.textID", "st.value AS phrase", "tp.translated AS translation");
        $sel->fields()->setExpression(" COALESCE(tp.trID, -1) ", "trID");
        $sel->fields()->setExpression(" COALESCE(tp.langID, $langID) ", "langID");

        $sel->from = " site_texts st LEFT JOIN translation_phrases tp ON tp.textID=st.textID AND tp.langID=$langID ";

        return new SQLQuery($sel, "textID");
    }

    //    public function processTranslation($phrase, $translated, $langID)
    //    {
    //        // 		ob_start();
    //        // 		var_dump($_POST);
    //
    //        $stb = new SiteTextsBean();
    //        $textID = (int)$stb->id4phrase($phrase);
    //
    //        // 		echo "TextID:$textID";
    //
    //        $translated = trim($translated);
    //
    //        // 		echo $translated;
    //
    //        $langID = (int)$langID;
    //
    //        $qry = $this->query();
    //        $qry->select->where = " langID='$langID' and textID='$textID' ";
    //        $qry->select->limit = " 1 ";
    //        $num = $qry->exec();
    //
    //        $trID = -1;
    //        $trow = array();
    //
    //        if ($trow = $qry->next()) {
    //            $trID = (int)$trow["trID"];
    //        }
    //
    //        // 		$debug = ob_get_contents();
    //        // 		ob_end_clean();
    //        // 		@file_put_contents("/tmp/test.log",$debug, FILE_APPEND);
    //
    //        if (strlen($translated) == 0) {
    //            if ($trID > 0) {
    //                $this->delete($trID);
    //            }
    //        }
    //        else {
    //            $db = $this->db;
    //
    //            $trow["translated"] = $db->escape($translated);
    //            $trow["textID"] = (int)$textID;
    //            $trow["langID"] = (int)$langID;
    //
    //            if ($trID > 0) {
    //
    //                $this->update($trID, $data);
    //
    //            }
    //            else {
    //
    //                $trID = (int)$this->insert($data);
    //            }
    //
    //        }
    //        return $trID;
    //
    //    }

    //    public function fetchTranslation($phrase, $langID)
    //    {
    //        $stb = new SiteTextsBean();
    //        $textID = (int)$stb->id4phrase($phrase);
    //        $ret = "";
    //
    //        $qry = $this->query();
    //        $qry->select->where = " langID='$langID' AND textID='$textID' ";
    //        $qry->select->limit = " 1 ";
    //        $num = $qry->exec();
    //
    //        if ($trow = $qry->next()) {
    //            $ret = $trow["translated"];
    //        }
    //
    //        return $ret;
    //    }
}

?>