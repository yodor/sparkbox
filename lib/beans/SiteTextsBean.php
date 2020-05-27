<?php
include_once("beans/DBTableBean.php");

class SiteTextsBean extends DBTableBean
{

    protected $createString = "
CREATE TABLE `site_texts` (
 `textID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `value` text COLLATE utf8_unicode_ci NOT NULL,
 `hash_value` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
 PRIMARY KEY (`textID`),
 UNIQUE KEY `hash_value` (`hash_value`)
) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci
";

    public function __construct()
    {
        parent::__construct("site_texts");
    }

    public function id4phrase(string $str) : int
    {
        $textID = -1;

        $str = html_entity_decode(stripslashes(trim($str)));

        $strdb = $this->db->escape($str);

        $qry = $this->query();
        $qry->select->fields()->set("textID");
        $qry->select->where()->add("hash_value", "'".md5($strdb)."'");
        $qry->select->limit = " 1 ";

        if ($qry->exec() && $data = $qry->next()) {

            $textID = (int)$data["textID"];
        }
        else {
            debug("Phrase hash not found in DB");

            $data = array();
            $data["hash_value"] = md5($strdb);
            $data["value"] = $strdb;

            $textID = $this->insert($data);

        }

        return $textID;
    }
}