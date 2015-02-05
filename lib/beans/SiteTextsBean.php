<?php
include_once ("lib/beans/DBTableBean.php");

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



	public function __construct() {
		parent::__construct("site_texts");
	}

	public function id4phrase($str)
	{
		$textID = -1;

		
		
		$str = html_entity_decode(stripslashes(trim($str)));


		$strdb = $this->db->escapeString($str);

		$num = $this->startIterator("WHERE hash_value = md5('$strdb') LIMIT 1");
		
// 		debug("SiteTextsBean::id4phrase: $str | is_found: $num");

		if ($num>0){
			
			if ($this->fetchNext($strow)){
				$textID = (int)$strow["textID"];
			}
			else {
				
				throw new Exception("DBError could not fetch text for translation: ".$this->getError());
			}
		}
		else {
			//can not find translatable phrase. insert into table to allow translation from cms
			
			$strow["hash_value"]=md5($str);
			$strow["value"]=$strdb;

			$textID = $this->insertRecord($strow);
			
			if ($textID<1) {
// 			  debug("SiteTextsBean::id4phrase: $str | DBVALUE: $strdb | HASH: ".$strow["hash_value"]);
// 			  debug("SiteTextsBean::id4phrase: insert error: ".$this->getError());
			}

// 			
		}

		return $textID;
	}
}