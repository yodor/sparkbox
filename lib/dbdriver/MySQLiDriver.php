<?php

include_once("lib/dbdriver/DBDriver.php");

class MySQLiDriver extends DBDriver
{
    private $connection = NULL;
    
	public static $conn_count = 0;
	
    public function __construct(DBConnectionProperties $conn, $open_new = true, $need_persistent=false)
    {
     
// 		$retry = 0;
// 		$retry_max = 5;
// 		$ex = NULL;
// 		while ($retry < $retry_max) {
// 		
// 		  try {
                                // in php 5.3 and up only
				if ($need_persistent) {
				  $this->connection = mysqli_connect("p:".$conn->host, $conn->user, $conn->pass, $conn->database, $conn->port);
				}
				else {
				  $this->connection = mysqli_connect($conn->host, $conn->user, $conn->pass, $conn->database, $conn->port);
				}
	
				if (mysqli_connect_errno())
				{
					throw new Exception("Unable to connect to database server(".MySQLiDriver::$conn_count."): ".mysqli_connect_error());
				}

				mysqli_autocommit( $this->connection, false );
				mysqli_set_charset( $this->connection , "utf8" );
				
// 				mysqli_query("SET NAMES 'UTF8' COLLATE 'utf8_general_ci' ",$this->connection);
// 				mysqli_query("SET foreign_key_checks = 1;",$this->connection);

			  // 	$vars = $conn->getVariables();
			  // 	foreach($vars as $dbvar=>$phpvar) {
			  // 	    global $$phpvar;
			  // 	    debug("Connection  @$dbvar = ".$$phpvar);
			  // 	    if (!mysql_query("SET @$dbvar = '".$$phpvar."';",$this->connection)) {
			  // 		debug("Unable to set @$dbvar variable to value: ".$$phpvar);
			  // 	    }
			  // 	    else {
			  // 		debug("@$dbvar variable is now set to value: ".$$phpvar);
			  // 	    }
			  // 	}
			  
			  MySQLiDriver::$conn_count++;
			  $ex= NULL;
// 			  break;
// 		  }
// 		  catch (Exception $e) {
// 			  $retry++;
// 			  sleep(2);
// 			  $ex = $e;
// 		  }
/*		  
		}
		if ($ex) throw $ex;*/
      
    }
    
	public function __destruct()
	{
		$this->shutdown();
	}
	
	public function shutdown()
    {
		if (is_resource($this->connection)) mysqli_close($this->connection);

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
	    $res = mysqli_query($this->connection, $str);

	    if (!$res) $this->error = mysqli_error($this->connection);

	    return $res;
    }
    
    public function numRows($res)
    {
	    return mysqli_num_rows($res);
    }
    
    public function numFields($res)
    {
	    return mysqli_num_fields($res);
    }
    
    public function fieldName($res,$pos)
    {
	    $arr =  mysqli_fetch_fields($res);
	    return $arr[$pos];
    }
    
    public function fetch($res)
    {
	    if (! ($res instanceof mysqli_result)) throw new Exception("No valid result  passed");

	    $ret = mysqli_fetch_assoc($res) or $this->error = mysqli_error($this->connection);

	    return $ret;
    }
    
    public function fetchArray($res)
    {
		if (! ($res instanceof mysqli_result)) throw new Exception("No valid result  passed");
		
	    $ret = mysqli_fetch_array($res) or $this->error = mysqli_error($this->connection);

	    return $ret;
    }

    public function fetchRow($res)
    {
	    if (! ($res instanceof mysqli_result)) throw new Exception("No valid result  passed");

	    $ret = mysqli_fetch_row($res) or $this->error = mysqli_error($this->connection);

	    return $ret;
    }
    
    public function free($res) 
    {
		if ($res instanceof mysqli_result) {
		  @mysqli_free_result($res);
		}
    }
    
    public function lastID()
    {
		return mysqli_insert_id($this->connection);
    }
    
    public function commit()
    {
	    $ret = mysqli_query($this->connection, "COMMIT")  or $this->error=mysqli_error($this->connection);
	    return $ret;
    }
    
    public function rollback()
    {
	    $ret = mysqli_query($this->connection, "ROLLBACK") or $this->error = mysqli_error($this->connection);
		return $ret;
    }
    
    public function transaction()
    {
		
	    $ret = mysqli_query($this->connection, "START TRANSACTION") or $this->error = mysqli_error($this->connection);
		$ret = mysqli_query($this->connection, "BEGIN") or $this->error = mysqli_error($this->connection);
		return $ret;
    }
    
    public function escapeString($data)
    {
	    return mysqli_real_escape_string($this->connection, $data);
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
		if (!$found) throw new Exception("Field [$field_name] does not exist in table: $table");
		return $ret;
    }
    //enum('T1','TIR','CIM')
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
		if (!$ret) throw new Exception("Unable to fecth found_rows(): ".$this->getError());
		$row = $this->fetch($ret);
		return $row["total"];
    }
}

?>
