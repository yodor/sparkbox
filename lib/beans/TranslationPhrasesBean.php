<?php
include_once("lib/beans/DBTableBean.php");
include_once("lib/beans/SiteTextsBean.php");

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

    public static function needBeans()
    {
        return array("LanguagesBean", "SiteTextsBean");
    }

    public function __construct()
    {
        parent::__construct("translation_phrases");
    }

    public function processRecord($trID, $trow)
    {
        $lastid = -1;

        if ($trID > 0) {
            $str_operation = "Update";
            $lastid = (int)$this->update($trID, $trow);
        }
        else {
            $str_operation = "Insert";
            $lastid = (int)$this->insert($trow);
        }
        if ($lastid < 1) {
            return $str_operation . " - " . $this->getError();
        }
        return $lastid;
    }

    public function processTranslation($phrase, $translated, $langID)
    {
        // 		ob_start();
        // 		var_dump($_POST);

        $stb = new SiteTextsBean();
        $textID = (int)$stb->id4phrase($phrase);

        // 		echo "TextID:$textID";

        $translated = trim($translated);

        // 		echo $translated;

        $langID = (int)$langID;

        $qry = $this->query();
        $qry->select->where = " langID='$langID' and textID='$textID' ";
        $qry->select->limit = " 1 ";
        $num = $qry->exec();

        $trID = -1;
        $trow = array();

        if ($trow = $qry->next()) {
            $trID = (int)$trow["trID"];
        }

        // 		$debug = ob_get_contents();
        // 		ob_end_clean();
        // 		@file_put_contents("/tmp/test.log",$debug, FILE_APPEND);

        if (strlen($translated) == 0) {
            if ($trID > 0) {
                $this->deleteID($trID);
            }
        }
        else {
            $db = $this->db;

            $trow["translated"] = $db->escapeString($translated);
            $trow["textID"] = (int)$textID;
            $trow["langID"] = (int)$langID;

            return $this->processRecord($trID, $trow);
        }
        return -1;

    }

    public function fetchTranslation($phrase, $langID)
    {
        $stb = new SiteTextsBean();
        $textID = (int)$stb->id4phrase($phrase);
        $ret = "";

        $qry = $this->query();
        $qry->select->where = " langID='$langID' AND textID='$textID' ";
        $qry->select->limit = " 1 ";
        $num = $qry->exec();

        if ($trow = $qry->next()) {
            $ret = $trow["translated"];
        }

        return $ret;
    }
}

?>