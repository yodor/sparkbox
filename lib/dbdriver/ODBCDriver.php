<?php

include_once("lib/dbdriver/DBDriver.php");

class ODBCDriver extends DBDriver
{
	
	protected function init()
	{	
		//defined in config/config.php
		global $db_name, $db_user, $db_pass, $db_host;
// 		echo "Connection parameters: <br>DB_HOST:$db_host<br>DB_USER:$db_user<br>DP_PASS:$db_pass<br>DB_NAME:$db_name ";
		
		$this->link = @odbc_connect($db_name, $db_user, $db_pass);
		if ($this->link){
			@odbc_autocommit($this->link, FALSE);
		}
		else {
			throw new Exception("Unable to connect to database server: ".$this->errormsg());
		}

	}
	protected function errormsg()
	{
		return odbc_errormsg();
	}
	public function query($str) {
		$ret = odbc_exec($this->link, $str) or $this->error=$this->errormsg();
		return $ret;
	}
	public function numRows($res)
	{
		return odbc_num_rows($res);
	}
	public function fetchFields($res){
		$arr = array();
		$num = odbc_num_fields($res);
		for ($a=1;$a<=$num;$a++){
			$name = odbc_field_name($res,$a);
			$len = odbc_field_len($res,$a);
			$type = odbc_field_type($res,$a);
			$arr[]=array("name"=>$name,"len"=>$len, "type"=>$type);
		}
		return $arr;
	}
	
	public function fetchTables($str="")
	{
		return odbc_tables($this->link);
	}
	
	public function fetch($str){
		$ret = odbc_fetch_array($str);
		if ($ret===FALSE)
		{
			return FALSE;
		}
		else if (is_array($ret)){
			return $ret;
		}
		else {
			throw new Exception($this->errormsg());
		}
		
	}
	public function fetchRow($str){
		$ret = odbc_fetch_row($str);
		if (!$ret)
		{
			$this->error=$this->errormsg();
		}
		return $ret;
	}
	public function lastID()
	{
// 			SELECT * FROM tbl WHERE auto IS NULL;
			return $this->query($this->link, "SELECT LAST_INSERT_ID()");

	}
	public function commit(){
		$ret=odbc_commit($this->link);
	}
	public function rollback(){
		$ret=odbc_rollback($this->link);
	}
	public function transaction(){
		//$ret=mysql_query("START TRANSACTION");
	}
	public function escapeString(&$data)
	{
		return $data;
	}
	public function shutdown()
	{

		odbc_close($this->link);
	}
}

?>