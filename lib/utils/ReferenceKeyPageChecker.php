<?php
include_once("lib/beans/DBTableBean.php");

//TODO:Check Usage
class ReferenceKeyPageChecker
{
  public $ref_key=false;
  public $ref_id=false;
  public $ref_row=array();
  public $qrystr="";
  
  public function __construct(DBTableBean $ref_bean, $redirect_fail)
  {
	
	try {
	  $ref_key = $ref_bean->getPrKey();

	  if (!isset($_GET[$ref_key]))throw new Exception($ref_key." not passed");
	  $ref_id = (int)$_GET[$ref_key];

	  $ref_row = $ref_bean->getByID($ref_id);
	  
	  $arr=$_GET;

	  if (isset($arr[$ref_key]))unset($arr[$ref_key]);
	  $this->qrystr = queryString($arr, $ref_key."=".$ref_id);
	  $this->ref_key = $ref_key;
	  $this->ref_id = $ref_id;
	  $this->ref_row = $ref_row;
	}
	catch (Exception $e) {
	  Session::set("alert", "Reference ".$this->ref_key."=".$this->ref_id." of ".get_class($ref_bean)." was not found.");
	  header("Location: $redirect_fail");
	  exit;
	}

	
  }


}

?>