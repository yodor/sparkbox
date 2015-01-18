<?php
include_once("lib/beans/SiteTextsBean.php");
include_once("lib/beans/TranslationBeansBean.php");
include_once("lib/handlers/JSONRequestHandler.php");


class TranslateBeanAjaxHandler extends JSONRequestHandler
{

  protected $langID = -1;
  protected $beanID = -1;
  
  protected $field_name = NULL;
  protected $bean_class = NULL;

  public function __construct()
  {
      parent::__construct("bean_translator");

  }
  
  public static function languageAlertText()
  {
      return tr("Please select translation language");
  }
  protected function parseParams() 
  {
  
      parent::parseParams();

      if (!isset($_GET["langID"])) throw new Exception("langID not passed");
      $this->langID = (int)$_GET["langID"];
      
      if (!isset($_GET["beanID"])) throw new Exception("beanID not passed");
      $this->beanID = (int)$_GET["beanID"];

      if (!isset($_GET["field_name"])) throw new Exception("field_name not passed");
      $this->field_name = DBDriver::get()->escapeString($_GET["field_name"]);
      

      if (!isset($_GET["bean_class"])) throw new Exception("bean_class not passed");
      $this->bean_class = $_GET["bean_class"];


      if ($this->langID<1) throw new Exception(self::languageAlertText());

      if ($this->beanID<1) throw new Exception("bean_id parameter incorrect");

      $bean = new $this->bean_class();
      $this->table_name = $bean->getTableName();

  }

  

  protected function _store(JSONResponse $ret)
  {

      global $g_bt;
      $trow = array();

      $g_bt->startIterator(" WHERE table_name='{$this->table_name}' AND field_name='{$this->field_name}' AND bean_id='{$this->beanID}' AND langID='{$this->langID}' LIMIT 1");

      $btID = -1;

      if ($g_bt->fetchNext($trow)) {
	  $btID = $trow[$g_bt->getPrKey()];
      }
      
      $trow["translated"] = DBDriver::get()->escapeString(trim($_REQUEST["translation"]));
      if (strlen($trow["translated"])<1) throw new Exception(tr("Input a text to be used as translation"));
      
      $trow["langID"] = $this->langID;
      $trow["field_name"] = $this->field_name;
      $trow["table_name"] = $this->table_name;
      $trow["bean_id"] = $this->beanID;
      
      if ($btID>0) {
	  if (!$g_bt->updateRecord($btID, $trow)) throw new Exception($g_bt->getError());
	  $ret->message = tr("Translation Updated");
      }
      else {
	  $btID = $g_bt->insertRecord($trow);
	  if ($btID<1) throw new Exception($g_bt->getError());
	  $ret->message = tr("Translation Stored");
      }

      $ret->btID = $btID;


  }
  protected function _fetch(JSONResponse $ret)
  {


      global $g_bt;

      $sql = " WHERE table_name='{$this->table_name}' AND field_name='{$this->field_name}' AND bean_id='{$this->beanID}' AND langID='{$this->langID}' LIMIT 1";
      
      $g_bt->startIterator($sql);

      $ret->translation = "";
      
      if ($g_bt->fetchNext($trow)) {
	  $ret->translation = $trow["translated"];
      }
      else {
	  $ret->message = tr("No translation found for selected language.")."<BR>".tr("Input the translation into the translation text area and click 'Translate'");
      }

  }

  protected function _clear(JSONResponse $ret)
  {

      global $g_bt;

      $sql = " WHERE table_name='{$this->table_name}' AND field_name='{$this->field_name}' AND bean_id='{$this->beanID}' AND langID='{$this->langID}' LIMIT 1";
      
      $g_bt->startIterator($sql, $g_bt->getPrKey());

      $ret->translation = "";
      
      if ($g_bt->fetchNext($trow)) {
	  
	  $g_bt->deleteID($trow[$g_bt->getPrKey()]);
	  $ret->message = tr("Translation was removed");
      }
      else {
	$ret->message = tr("No translated phrase found.");
      }
      
  }

}
?>