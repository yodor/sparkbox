<?php

include_once ("lib/beans/DBTableBean.php");

class DBViewBean extends DBTableBean
{
	public function __construct($table_name){
		parent::__construct($table_name);
	}

	public function deleteID($id){
		throw new Exception("View not writable");
	}
	public function deleteRef($refkey, $refval){
		throw new Exception("View not writable");
	}
	public function toggleField($id, $field){
		throw new Exception("View not writable");
	}	
	public function updateRecord($id, &$row, &$db = false){
		throw new Exception("View not writable");
	}
	
	//
	 
	public function insertRecord(&$row, &$db = false)
	{
		throw new Exception("View not writable");
	}




}

?>