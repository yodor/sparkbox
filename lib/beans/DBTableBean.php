<?php
include_once ("lib/dbdriver/DBDriver.php");
include_once ("lib/beans/IDataBean.php");

abstract class DBTableBean implements IDataBean
{

    protected $table = "";

    protected $createString = "";

    protected $filter = false;

    protected $last_iterator_sql = "";

    protected $db = NULL;

    protected $storage_types = NULL;

    protected $iterator = NULL;

    protected static $instances = array();
    
    public function __construct($table_name, $dbdriver=NULL)
    {
	$this->table=$table_name;

	if ($dbdriver) {
	    $this->db = $dbdriver;
	}
	else {
	    global $g_db;
	    $this->db = $g_db;
	}

	$bclass = get_class($this);
	
	if (!$this->db)throw new Exception("DBTableBean::$bclass - CTOR | Could not attach with DBDriver");
	
	$this->initFields();
	
	
	
	if (!isset(self::$instances[$bclass])) {
	    self::$instances[$bclass] = $this;
	}
    }
    public static function instance($class)
    {
	if (isset(self::$instances[$class])) {
	  return self::$instances[$class];
	}
	return new $class();
    }
    public function __destruct()
    {
	if(is_resource($this->iterator))$this->db->free($this->iterator);
    }

    protected function initFields() 
    {

      $this->storage_types = array();

      if (! $this->db->tableExists($this->table) ){

	  if (strlen($this->createString)<1)throw new Exception("Table: '{$this->table}' was not found in the active connection. No create string set to recreate the table.");
	  $this->createTable();
      }

      if (! $this->db->tableExists($this->table) ) throw new Exception("Table: '{$this->table}' is not available in the active connection.");

      $ret = $this->db->queryFields($this->table);

      while ($row=$this->db->fetch($ret))
      {
	  if (strcmp($row["Key"],"PRI")==0){
		  $this->prkey=$row["Field"];
	  }

	  $field_name = $row["Field"];
	  
	  $this->fields[]=$field_name;

	  $this->storage_types[$field_name]=$row["Type"];
      }

//       debugArray("Storage Types for Bean: ".get_class($this), $this->storage_types);

    }
    
    protected function createTable()
    {
	$this->db->transaction();
	$this->db->query($this->createString);
	$this->db->commit();
    }
    
    public function getTableName()
    {
	return $this->table;
    }

    public function getError()
    {
	return $this->db->getError();
    }

    public function setDB(DBDriver $db)
    {
	$this->db = $db;
    }

    public function getDB()
    {
	return $this->db;
    }

    public function getPrKey()
    {
	return $this->prkey;
    }

    public function getFields()
    {
	    return $this->fields;
    }

    public function getStorageTypes()
    {
	    return $this->storage_types;
    }

    public function getSelectQuery()
    {
	$select = new SelectQuery();
	$select->fields=" * ";
	$select->from = $this->table;
	$select->where = $this->filter;

	return $select;

    }
    public function getLastIteratorSQL()
    {
	return $this->last_iterator_sql;
    }

    public function setFilter($sqlfilter)
    {
	$this->filter = $sqlfilter;
    }
    
    public function getCount()
    {

	$ret = $this->db->query("SELECT count(*) as cnt from {$this->table} ");
	$row=$this->db->fetch($ret);
	return (int)$row["cnt"];
    }
	
    public function containsValue($key, $val)
    {
	$total=0;
	$val = $this->db->escapeString($val);
	$sql = "SELECT SQL_CALC_FOUND_ROWS * from {$this->table} WHERE $key LIKE '$val' LIMIT 1";
	$itr = $this->createIterator($sql, $total);
	$this->db->free($itr);
	if ($total>0) return TRUE;
	return FALSE;
    }

    public function haveField($field_name)
    {
	return in_array($field_name, $this->fields);
    }

    public function startFieldIterator($filter_field, $filter_value) 
    {
	return $this->startIterator("WHERE $filter_field='$filter_value'");
    }
    
    public function startSelectIterator(SelectQuery $select)
    {
	$total = -1;
	$sql = $select->getSQL();

	if (is_resource($this->iterator))$this->db->free($this->iterator);
	
	$this->iterator = $this->createIterator($sql, $total);
	
	return $total;
    }

    public function createIteratorSQL($filter="", $fields=" * ")
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

	$sql = "SELECT SQL_CALC_FOUND_ROWS $fields FROM {$this->table} $itr_filter ";
	return $sql;
	
    }

    public function createIterator($sql, &$total)
    {
	$this->last_iterator_sql=$sql;

// 	debug("DBTabelBean::createIterator | SQL: $sql");
	 
	$itr = $this->db->query($sql);

	if (!$itr) {
	
	    debug("DBTabelBean::createIterator | Unable to create iterator for SQL: $sql");
	    
	    throw new Exception("Unable to create iterator: ".$this->db->getError());
	}
	$ret = $this->db->query("SELECT FOUND_ROWS() as total");
	$row = $this->db->fetch($ret);
	$this->db->free($ret);

	$total = (int)$row["total"];
	return $itr;
    }
    
    public function startIterator($filter="", $fields=" * ")
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

	if (is_resource($this->iterator))$this->db->free($this->iterator);
	$this->iterator = $this->createIterator($sql, $total);

	return $total;
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

    private function fillDebug()
    {
	    ob_start();
	    $usedby = debug_backtrace();
	    print_r($usedby);
	    $trace = ob_get_contents();
	    ob_end_clean();
	    return $trace;
    }
    
    public function getByID($id, $db=false, $fields=" * ")
    {
	if (!$db)$db=$this->db;

	$sql = "SELECT $fields from {$this->table} WHERE {$this->prkey}='$id'";
	$ret = $db->query($sql);

	if (!$ret){
	    throw new Exception("DBError: ".$db->getError());
	}

	$row=$db->fetch($ret);
	$db->free($ret);

	if (!$row) {
	    throw new Exception("No such ID");
	}
	return $row;
    }
    
    public function getByRef($refkey, $refid)
    {

	$refkey = $this->db->escapeString($refkey);
	$refid = (int)$refid;
	$sql = "SELECT * FROM {$this->table} WHERE $refkey='$refid' LIMIT 1";
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

    public function deleteID($id, $db=false)
    {
        $docommit=false;

        if (!$db) {
            $db = $this->db;
	    $db->transaction();
            $docommit=true;
        }

        $qry = "DELETE FROM {$this->table} WHERE {$this->prkey}=$id";

	$res = $db->query($qry);
	
	if (!$res) {
	  if ($docommit) $db->rollback();
	  throw new Exception("DBError: ".$db->getError());
	}

	if ($docommit) $db->commit();
	return $res;
    }
	
    public function deleteRef($refkey, $refval, $db=false, $keep_ids=array())
    {
	$docommit=false;
	if (!$db) {
	    $db = $this->db;
	    $db->transaction();
	    $docommit=true;
	}
	
	$sql = "DELETE FROM {$this->table} WHERE $refkey='$refval'";

	if (count($keep_ids)>0) {
	  $keep_list_ids = implode(",", $keep_ids);
	  $sql.= " AND ({$this->prkey} NOT IN ($keep_list_ids)) ";
	}

	debug("DBTableBean::deleteRef: Executing SQL: $sql");

	$res = $db->query($sql);

	if (!$res) {
	  if ($docommit) $db->rollback();
	  throw new Exception("DBError: ".$db->getError());
	}
	
	if ($docommit) $db->commit();

	return $res;
    }

    
    public function toggleField($id, $field)
    {
	if (!in_array($field, $this->fields)) throw new Exception("DBTableBean::toggleField Field '$field' not found in this bean");
	
	$id = (int)$id;
	
	$db = DBDriver::factory();
	$field = $db->escapeString($field);
	
	try {
	
	  $db->transaction();
	
	  if (!$db->query("UPDATE {$this->table} SET `$field` = NOT `$field` WHERE {$this->prkey}=$id "))throw new Exception("DBTableBean::toggleField DB Error: ".$db->getError());
	
	  $db->commit();
	}
	catch (Exception $e) {
	  $db->rollback();
	  throw $e;
	}
    }

    public function findFieldValue($field_name, $field_value)
    {
	    $field_name = $this->db->escapeString($field_name);
	    
	    $res = $this->db->query("SELECT {$this->prkey}, $field_name FROM {$this->table} WHERE $field_name='$field_value' LIMIT 1");
	    if (!$res) throw new Exception("DBTableBean::findFieldValue DB Error: ".$this->db->getError());
	    
	    return $this->db->fetch($res);
    }
    public function fieldValue($id, $field_name)
    {
	    $id = (int)$id;

	    $field_name = $this->db->escapeString($field_name);

	    $res = $this->db->query("SELECT {$this->prkey}, `$field_name` FROM {$this->table} WHERE {$this->prkey}=$id ");
	    if (!$res) throw new Exception("DBTableBean::fieldValue DB Error: ".$this->db->getError());

	    if ($row = $this->db->fetch($res)) {
		return $row[$field_name];
	    }

	    return NULL;
    }
    public function needQuotes($key, &$value="")
    {
	  $storage_type = $this->storage_types[$key];
// 	  if (strpos($storage_type,"char")!==false || strpos($storage_type,"text")!==false || strpos($storage_type,"blob")!==false  ||  strpos($storage_type,"enum")!==false) {
// 		return true;
// 	  }
// 	  if (strpos($storage_type, "bool")!==false) {
// 		return true;
// 	  }
	  if (strpos($storage_type,"date")!==false || strpos($storage_type,"timestamp")!==false) {
		  if (endsWith($value , "()")) return false;
		  return true;
	  }
	  return true;
    }

    public function insertRecord(&$row, &$db=false)
    {
    
	$last_insert=-1;

	$docommit=false;

	if (!$db) {
	  $db = $this->db;
	  $db->transaction();
	  $docommit=true;
	}

	$values = array();
	$this->prepareInsertValues($row, $values);

	$sql = "INSERT INTO {$this->table} (".implode(",",array_keys($values)).") VALUES (".implode(",", $values).")";

	if (defined("DEBUG_DBTABLEBEAN_DUMP_SQL")) {
	  debug(get_class($this)." INSERT SQL: $sql");
	}
	
	$ret = $db->query($sql);

	if ($ret === false) {
	  if ($docommit) $db->rollback();
	  return -1;
	}

	//NOTE!!! lastID return the first auto_increment of a multi insert transaction
	$last_insert = $db->lastID();

	if ($docommit) $db->commit();

	return $last_insert;
    }
	
    public function updateRecord($id, &$row, &$db=false)
    {

	$docommit = false;

	if (!$db) {
	  $db = $this->db;
	  $db->transaction();
	  $docommit=true;
	}

	$values = array();
	$this->prepareUpdateValues($row, $values);

	$sql = "UPDATE {$this->table} SET ".implode(",",$values)." WHERE {$this->prkey}=$id";

	if (defined("DEBUG_DBTABLEBEAN_DUMP_SQL")) {
	  debug(get_class($this)." UPDATE SQL: $sql");
	}
	
	$ret = $db->query($sql);

	if ($ret === false) {
	  if ($docommit) $db->rollback();
	  return false;
	}

	if ($docommit) $db->commit();

	return $id;
    }
	
    protected function prepareValues(&$row, &$values, $for_update)
    {
		$keys = array();

		foreach ($row as $key=>$val) {
		  //drop keys that are not fields from 'this' table
		  if (!in_array($key, $this->fields)) continue;
		  $keys[] = $key;
		}

		$values = array();

		foreach ($keys as $idx=>$key) {
		  $value = $row[$key];
	// 	  debug("Checking key='$key' : Value: ".$value. " STRLEN: ".strlen($value));

		  if (is_array($value)) {
			
			  if (count($value)<1)continue;
			  $value = $value[0];
			
		  }
		  
		  if (is_null($value)) {
			  $values[$key] = "NULL";
		  }
		  
		  else {
			  if ($this->needQuotes($key, $value)===true) {
				  $values[$key] = "'".$value."'";
			  }
			  else {
				  $values[$key] = $value;
			  }
		  }
		  
		  if ($for_update===true) {
			
			  $values[$key]="$key=".$values[$key];//already quoted
			
		  }
		}
    }
    
    protected function prepareInsertValues(&$row, &$values)
    {
	$this->prepareValues($row, $values, false);
    }
    
    protected function prepareUpdateValues(&$row, &$values)
    {
	$this->prepareValues($row, $values, true);
    }

}

?>