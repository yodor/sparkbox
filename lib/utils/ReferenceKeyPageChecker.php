<?php
include_once("lib/beans/DBTableBean.php");

//TODO:Check Usage
class ReferenceKeyPageChecker
{
  public $ref_key=null;
  public $ref_id=-1;
  public $ref_row=array();
  public $qrystr="";

  public function __construct(DBTableBean $ref_bean, $redirect_fail)
  {
	
	
	try {
	  $ref_key = $ref_bean->getPrKey();
	  $this->ref_key = $ref_key;

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
	
	  if ($redirect_fail) {
		Session::set("alert", "Required parameter ".$this->ref_key." of ".get_class($ref_bean)." was not found.");
		header("Location: $redirect_fail");
		exit;
	  }
	  else {
		  throw $e;
	  }
	}

	
  }


}

?>