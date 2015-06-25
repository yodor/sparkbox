<?php

include_once("lib/dbdriver/DBDriver.php");

class MySQLDriver extends DBDriver
{
    private $connection = NULL;
	protected $is_persistent = false;
	
    public function __construct(DBConnectionProperties $conn, $open_new = true, $use_persistent=false)
    {
      $retry = true;
      
      $retry_max = 10;
      $retry_count = 0;

      while ($retry) {
      
		try {
			if ($use_persistent) {
			  @$this->connection = mysql_pconnect($conn->host.":".$conn->port, $conn->user, $conn->pass);
			  //error_log("using persistent connection: ".$this->connection." for request: ".$_SERVER["REQUEST_URI"]);
			}
			else {
			  @$this->connection = mysql_connect($conn->host.":".$conn->port, $conn->user, $conn->pass,$open_new);
			  //error_log("opening new connection: ".$this->connection." for request: ".$_SERVER["REQUEST_URI"]);
			}
			
			if (!is_resource($this->connection)) throw new Exception("Unable to connect to database server: ".mysql_error());

			if (mysql_select_db($conn->database,$this->connection)!==TRUE) {
			  throw new Exception("Unable to select database: ".mysql_error($this->connection));
			}
			
			mysql_query("SET AUTOCOMMIT = 0 ",$this->connection);
			mysql_query("SET NAMES 'UTF8' COLLATE 'utf8_general_ci' ",$this->connection);
			mysql_query("SET foreign_key_checks = 1 ",$this->connection);


	// 	    try {
	// 		if (! mysql_query("SET time_zone='".ini_get("date.timezone")."'", $this->connection)) throw new Exception(mysql_error($this->connection));
	// 
	// 		
	// 	    }
	// 	    catch (Exception $e) {
	// 		debug("MySQLDriver::__construct: Unable to set timezone: ".$e->getMessage());
	// 	    }

			$vars = $conn->getVariables();
			foreach($vars as $dbvar=>$phpvar) {
				global $$phpvar;
				debug("Connection  @$dbvar = ".$$phpvar);
				if (!mysql_query("SET @$dbvar = '".$$phpvar."';",$this->connection)) {
				  debug("Unable to set @$dbvar variable to value: ".$$phpvar);
				}
				else {
				  debug("@$dbvar variable is now set to value: ".$$phpvar);
				}
			}
			$retry = false;
			$this->is_persistent = $use_persistent;
		}
		catch (Exception $e) {
			if ($retry_count<$retry_max) {
			  $retry_count++;
			  sleep(1);
			}
			else {
			  $retry = false;
			  throw new Exception("Error during mysql init: ".mysql_error().$e->getTraceAsString());
			}
		}



      }//while
      
      		
    }

    public function dateTime($add_days=0, $interval_type=" DAY ")
    {
	$res = $this->query("SELECT DATE_ADD(now(), INTERVAL $add_days $interval_type) as datetime");
	$row = $this->fetch($res);
	return $row["datetime"];
    }
    
    public function timestamp() 
    {
	$res = $this->query("SELECT UNIX_TIMESTAMP(CURRENT_TIMESTAMP) as datetime");
	$row = $this->fetch($res);
	return $row["datetime"];
    }
    
    public function query($str) 
    {
	$res = mysql_query($str, $this->connection);
	
	if (!$res) {

	  $this->error=mysql_error($this->connection);

	}
	
	return $res;
    }
    
    public function numRows($res)
    {
	return mysql_num_rows($res);
    }
    
    public function numFields($res)
    {
	return mysql_num_fields($res);
    }
    
    public function fieldName($res,$pos)
    {
	return mysql_field_name($res,$pos);
    }
    
    public function fetch($str)
    {
		if (!is_resource($str)) throw new Exception("No valid resource passed");

		$ret = mysql_fetch_assoc($str) or $this->error=mysql_error($this->connection);

		return $ret;
    }
    
    public function fetchArray($str)
    {
	$ret = mysql_fetch_array($str) or $this->error=mysql_error($this->connection);

	return $ret;
    }

    public function fetchRow($str)
    {
	if (!is_resource($str)) throw new Exception("No valid resource passed");

	$ret = mysql_fetch_row($str) or $this->error=mysql_error($this->connection);

	return $ret;
    }

    public function free($res) 
    {
	  if (is_resource($res)) {
		mysql_free_result($res);
	  }
    }
    
    public function lastID()
    {
	return mysql_insert_id($this->connection);
    }
    
    public function commit()
    {
	$ret=mysql_query("COMMIT", $this->connection) or $this->error=mysql_error($this->connection);
	$this->in_transaction = false;
	return $ret;
    }
    
    public function rollback()
    {
	$ret=mysql_query("ROLLBACK",$this->connection) or $this->error=mysql_error($this->connection);
	$this->in_transaction = false;
	return $ret;
    }

    public function isTransaction()
    {
	return $this->in_transaction;
    }
    
    // The correct way to use LOCK TABLES and UNLOCK TABLES with transactional tables, such as InnoDB tables, is to begin a transaction with SET autocommit = 0 (not START TRANSACTION) followed by LOCK TABLES, and to not call UNLOCK TABLES until you commit the transaction explicitly. For example, if you need to write to table t1 and read from table t2, you can do this:
    // 
    // SET autocommit=0;
    // LOCK TABLES t1 WRITE, t2 READ, ...;... do something with tables t1 and t2 here ...
    // COMMIT;
    // UNLOCK TABLES;
    // When you call LOCK TABLES, InnoDB internally takes its own table lock, and MySQL takes its own table lock. InnoDB releases its internal table lock at the next commit, but for MySQL to release its table lock, you have to call UNLOCK TABLES. You should not have autocommit = 1, because then InnoDB releases its internal table lock immediately after the call of LOCK TABLES, and deadlocks can very easily happen. InnoDB does not acquire the internal table lock at all if autocommit = 1, to help old applications avoid unnecessary deadlocks.
    // 
    // ROLLBACK does not release table locks.
    public function transaction()
    {
	$ret=mysql_query("START TRANSACTION", $this->connection) or $this->error=mysql_error($this->connection);
	$ret=mysql_query("BEGIN", $this->connection) or $this->error=mysql_error($this->connection);
	$this->in_transaction = true;
	
	return $ret;
    }
    
    public function escapeString($data)
    {
	return mysql_real_escape_string($data, $this->connection);
    }
    
    public function shutdown()
    {
	  if (!$this->is_persistent) {
		
		$is_closed = mysql_close($this->connection);
// 		error_log("Closing non-persistent connection: ".$this->connection." Success: ".$is_closed);

	  }
	  
    }

    public function queryFields($table)
    {

	return $this->query("show fields from $table");

    }
    
    public function fieldType($table, $field_name)
    {
	$found = false;
	$ret = false;
	$res = $this->queryFields($table);
	while ($row = $this->fetch($res)) {
		if (strcmp($row["Field"],$field_name)==0) {
			$ret = $row["Type"];
			$found=true;
			break;
		}
	}
	$this->free($res);
	if (!$found) throw new Exception("Field $field_name does not exist in table: $table");
	return $ret;
    }

    public static function enum2array($enum_str)
    {
	$enum_str = str_replace("enum(","",$enum_str);
	$enum_str = str_replace(")","",$enum_str);
	$enum_str = str_replace("'","",$enum_str);

	return explode(",", $enum_str);
    }
    
    public function tableExists($table)
    {
	$ret = $this->query("show tables like '$table'");
	$num = $this->numRows($ret);
	if ($num<1) return FALSE;
	return TRUE;
    }
    
    public function fetchTotalRows()
    {
	$ret = $this->query("SELECT FOUND_ROWS() as total");
	if (!$ret)throw new Exception("Unable to fecth found_rows(): ".$this->getError());
	$row = $this->fetch($ret);
	return $row["total"];
    }
}

?>