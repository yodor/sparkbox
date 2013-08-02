<?php
include_once ("lib/dbdriver/DBDriver.php");
include_once ("lib/beans/DataBean.php");

abstract class ForeignBlobDataBean implements DBTableBean
{

	protected $table = false;

	protected $createString = "";

	protected $filter = false;
	
	public $debug_sql = false;

	private $last_iterator_sql = "";

	private $db;

	protected $storage_types;

	public function getPrKey()
	{
	    return $this->prkey;
	}

	public function getLastIteratorSQL()
	{
		return $this->last_iterator_sql;
	}
	public function setFilter($sqlfilter)
	{
		$this->filter = $sqlfilter;
	}

	public function getTableName()
	{
		return $this->table;
	}
	
	public function getError()
	{
		return $this->db->getError(); 
	}
	
	
	public function __construct($table_name){
			$this->table=$table_name;
global $g_db;
			$this->db = $g_db;//DBDriver::factory();

			$this->initFields();
	}
	public function __destruct()
	{
		  if(is_resource($this->iterator))$this->db->free($this->iterator);
	}
	public function prepareEmptyResult(){
		$row = array();
		foreach($this->fields as $key=>$val){
			$row[$key]="";
		}
		return $row;
	}
	protected function initFields() {
		
		//$db = DBDriver::factory();
		$this->storage_types = array();
  
		if (! $this->db->tableExists($this->table) ){
			
			if (strlen($this->createString)<1)throw new Exception("Create string not specified for missing table: {$this->table}");

			$this->createTable();
		}

		if (! $this->db->tableExists($this->table) ) throw new Exception("Unavailable table: {$this->table}. DBError:<p>".$this->getError()."</p>");
		
		$ret = $this->db->queryFields($this->table);

		while ($row=$this->db->fetch($ret))
		{
			if (strcmp($row["Key"],"PRI")==0){
				$this->prkey=$row["Field"];
			}
			
			$this->fields[]=$row["Field"];
			
			$this->storage_types[$row["Field"]]=$row["Type"];
		}

		$this->db->free($ret);
	}
	protected function createTable()
	{
		$this->db->transaction();
		$this->db->query($this->createString);
		$this->db->commit();
	}
	
	public function getFields(){
		return $this->fields;
	}
	public function getStorageTypes()
	{
		return $this->storage_types;
	}
	public function getSqlAll($search_filter="", $fields=" * "){
		return "SELECT $fields FROM {$this->table} $search_filter";
	}


	public function startIterator($filter="", $fields=" * ", $debug=false)
	{
		$itr_filter = $filter;
		if ($this->filter) {
		  $itr_filter = trim($itr_filter);

		  $filter = str_ireplace("WHERE", "", $filter);
		  if (strpos($this->filter,"JOIN")!==false){
			$itr_filter = $this->filter." ".$filter;
		  }
		  else {
			$itr_filter = "WHERE {$this->filter}".$filter;
		  }
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS $fields FROM {$this->table}  $itr_filter ";
		$total = -1;
		if($debug) {echo "<p>DEBUG: ".$sql."</p>";}
		try {
		  if (is_resource($this->iterator))$this->db->free($this->iterator);
		  $this->iterator = $this->createIterator($sql, $total);
		}
		catch (Exception $e)
		{
		  echo "Filter:$filter<hr>";
		  echo "Len: ".strlen($filter);
		  echo $e;
		  echo "<Hr>";
		  echo $sql;
		  throw $e;
		}
		return $total;
	}
	public function createIterator($sql, &$total)
	{
		$this->last_iterator_sql=$sql;
	  	
		$itr = $this->db->query($sql);

		if (!$itr)throw new Exception("Unable to create iterator: ".$this->db->getError());
		
		$ret = $this->db->query("SELECT FOUND_ROWS() as total");
		$row = $this->db->fetch($ret);
		$this->db->free($ret);

		$total = (int)$row["total"];
		return $itr;
	}
	public function fetchNext(&$row, $iterator=false)
	{
		
		if ($iterator===false){
			$iterator=$this->iterator;
		}

		if (is_resource($iterator)) {
			return ($row=$this->db->fetch($iterator));
		}
		else {
			return false;
		}
	}

	public function getCount()
	{
		
		$ret = $this->db->query("SELECT count(*) as cnt from {$this->table} ");
		$row=$this->db->fetch($ret);
		return (int)$row["cnt"];
	}


	private function fillDebug()
	{
		ob_start();
		$usedby = debug_backtrace();
		print_r($usedby);
		$trace = ob_get_contents();
		ob_end_clean();
		return $trace;
	}
	public function getByID($id, $db=false)
	{
		if (!$db)$db=$this->db;//DBDriver::factory();
// 		$db->transaction();
		$sql = "SELECT * from {$this->table} WHERE {$this->prkey}=$id";
		$ret = $db->query($sql);	

		if (!$ret){

			ob_start();
			$usedby = debug_backtrace();
			var_dump($usedby);
			$trace = ob_get_contents();
			ob_end_clean();

// 			throw new Exception("DBError: ".$db->getError()." | StackTrace: ".$trace);
			throw new Exception("DBError: ".$db->getError()." | $sql ");

		}

		$row=$db->fetch($ret);
		$db->free($ret);

		if (!$row) {
			ob_start();
			$usedby = debug_backtrace();
			var_dump($usedby);
			$trace = ob_get_contents();
			ob_end_clean();
// 			throw new Exception("No such ID. | StackTrace: ".$trace);
			throw new Exception("No such ID: $id | Table: ".$this->table);
		}
		return $row;
	}
	public function getByRef($refkey, $refid)
	{

		$refkey = $this->db->escapeString($refkey);
		$refid = (int)$refid;
		$sql = "SELECT * FROM {$this->table} WHERE $refkey=$refid LIMIT 1";
		$ret = $this->db->query($sql);
		if (!$ret){
			throw new Exception("DBError: ".$this->db->getError());
		}
		$row = $this->db->fetch($ret);

		$this->db->free($ret);

		if (!$row) {
			return false;
		}
		return $row;
	}
	
	public function deleteID($id, $db=false){
        $docommit=false;

        if (!$db) {
            $db = $this->db;
			$db->transaction();
            $docommit=true;
        }
		
        $qry = "DELETE FROM {$this->table} WHERE {$this->prkey}=$id";

		$res = $db->query($qry);
		if (!$res) throw new Exception("Unable to delete: ".$db->getError());

		$res = $db->query("DELETE FROM storage WHERE refID=$id AND table_name='{$this->table}'");
		if (!$res) throw new Exception("Unable to delete old BLOB: ".$db->getError());


		if ($docommit) $db->commit();
		return $res;
	}
	public function deleteRef($refkey, $refval, $db=false)
	{
		 $docommit=false; 
		 if (!$db) {
            $db = $this->db;
            $db->transaction();
            $docommit=true;
        }
		$res = $db->query("DELETE FROM {$this->table} WHERE $refkey='$refval'");	
		if ($docommit) $db->commit();	
		return $res;
	}
	public function toggleField($id, $field)
	{
		if (!in_array($field,$this->fields))return;	
		$this->db->transaction();
		$this->db->query("UPDATE {$this->table} SET $field = NOT $field WHERE {$this->prkey}=$id ");
		$this->db->commit();
	}	

	public function updateRecord($id, $row, &$db=false, $debug=false){

        $docommit = false;

		if (!$db) {
            $db = $this->db;
            $db->transaction();
            $docommit=true;
        }
		$set_str="";
		foreach ($row as $key=>$val){
			//drop keys that are not fields from 'this' table
			if (!in_array($key, $this->fields))continue;

			if (strlen($set_str)>0){
				$set_str.=" , ";
			}
			$pos = strpos($this->storage_types[$key], "blob");
			if ($pos!==FALSE) {
			  $val = "";
			}


			if (is_null($val) || strlen($val)<1) {
			  $set_str.=" $key=NULL ";
			}
			else {
	
			  $set_str.=" $key='$val' ";
			}
			
		}
		
		$sql = "UPDATE {$this->table} set $set_str WHERE {$this->prkey}=$id";
		if ($debug || $this->debug_sql) echo $sql;

		$ret = $db->query($sql);

		if (!$ret) throw new Exception($db->getError());

		
		//fight max_allowed_packet size
		foreach($this->storage_types as $key=>$val) {
			if (!isset($row[$key]))continue;
			
			if (strpos($val, "blob")!==FALSE) {
				$value = $row[$key];

				$res = $db->query("DELETE FROM storage WHERE refID=$id AND table_name='{$this->table}'");
				if (!$res) throw new Exception("Unable to delete old BLOB: ".$db->getError());

				$chunk_size = MAX_PACKET_SIZE/4;

				$size = strlen($value);
				$offset=0;
				$count=0;
				
				while ($offset<$size) {

				  $packet = substr($value, $offset, $chunk_size);  
				  $offset+=$chunk_size;
				  
				  $res = $db->query("INSERT INTO storage (refID, table_name, offset, data) VALUES ($id, '{$this->table}', $count, '".$db->escapeString($packet)."')");
				  if (!$res) throw new Exception("Unable to update BLOB contents: ".$db->getError());
				  
				  $count++;

				}

			}
		}

		if ($docommit)  $db->commit();

		return $id;
	}
	
	//
	 
	public function insertRecord($row, &$db=false, $debug=false)
	{
		$last_insert=-1;

		$docommit=false;

		if (!$db) {
            $db = $this->db;
            $db->transaction();
            $docommit=true;
        }

		$keys="";
		foreach ($row as $key=>$val){
		
		//drop keys that are not fields from 'this' table
			if (!in_array($key, $this->fields))continue;
			if (strlen($keys)>0){
				$keys.=" , ";
			}
			$keys.=" $key ";
		}
		$values="";

		
		$have_blob = false;

		foreach ($row as $key=>$val){
		
		//drop keys that are not fields from 'this' table
			if (!in_array($key, $this->fields))continue;
			if (strlen($values)>0){
				$values.=" , ";
			}
			if (is_null($val) || strlen($val)<1) {
			  $values.=" NULL ";
			}
			else {


			  $pos = strpos($this->storage_types[$key], "blob");

			  if ($pos!==FALSE) {
				$values.=" NULL ";
			  }
			  else {
				$values.=" '$val' ";
			  }
			}
		}


		

		$query = "INSERT INTO {$this->table} ($keys) VALUES ($values)";
		if ($debug) echo "QUERY: ".$query;
		
		$ret = $db->query($query);	

		if ($ret === FALSE) {

			  return -1;
		}

        //NOTE!!! lastID return the first auto_increment of a multi insert transaction
		$last_insert = $db->lastID();

		

		

		//fight max_allowed_packet size
		foreach($this->storage_types as $key=>$val) {
			if (!isset($row[$key]))continue;
			
			if (strpos($val, "blob")!==FALSE) {
				$value = $row[$key];

				$chunk_size = MAX_PACKET_SIZE/4;

				$size = strlen($value);

if ($size<=$chunk_size) $chunk_size=$size;

				$offset=0;
				$count=0;
				// $db->transaction();
				// try {

				while ($offset<$size) {

				  $packet = substr($value, $offset, $chunk_size);  
				  $offset+=$chunk_size;
				  
				  $res = $db->query("INSERT INTO storage (refID, table_name, offset, data) VALUES ($last_insert, '{$this->table}', $count, '".$db->escapeString($packet)."')");
				  if (!$res) throw new Exception("Unable to update BLOB contents: ".$db->getError());
				  
				  $count++;

				}

			}

		}

		if ($docommit) $db->commit();
		
		return $last_insert;
	}


	public function containsString($key,$val)
	{
		$total=0;
		
		$valesc = $this->db->escapeString($val);
		$sql = "SELECT SQL_CALC_FOUND_ROWS * from {$this->table} WHERE $key LIKE '$valesc' LIMIT 1";

		$itr = $this->createIterator($sql, $total);

		$this->db->free($itr);

		if ($total>0) return TRUE;
		return FALSE;
	}



	public function needTables()
	{
		return array();
	}


}

?>