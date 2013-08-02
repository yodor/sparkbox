<?php
include_once("lib/dbdriver/DBDriver.php");

class MySQLiResult  {

	private static $num_res = 0;

	private $result;

    public function __construct(mysqli_result $result) {
        $this->result = $result;
		MySQLiResult::$num_res++;

// echo "Num Results Created: ".MySQLiResult::$num_res;
    }

    public function __call($name, $arguments) {
        return call_user_func_array(array($this->result, $name), $arguments);
    }

    public function __set($name, $value) {
        $this->result->$name = $value;
    }

    public function __get($name) {


        return $this->result->$name;
    }
	public function __destruct()
	{

		@$this->result->free();
		MySQLiResult::$num_res--;
// echo "Num Results Free: ".MySQLiResult::$num_res;
	}
}

class MySQLiDriver extends DBDriver
{
	private $dbobj;

	protected function init()
	{

		//defined in config/config.php
		global $db_name, $db_user, $db_pass, $db_host, $db_port;

//  		echo "Connection parameters: <br>DB_HOST - $db_host:$db_port<br>DB_USER - $db_user<br>DP_PASS - $db_pass<br>DB_NAME - $db_name ";
		
		
		$retry = true;
		$retry_max = 3;
		$retry_count = 0;


		while ($retry) {
		  try {

			
			 $dbobj = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

			  if (mysqli_connect_errno()) {
				  throw new Exception("MySQLi Connect Error (".mysqli_connect_errno().") ".mysqli_connect_error());
			  }
			  
			  $dbobj->set_charset("utf8");
			
			
			  $retry = false;

			  $this->dbobj = $dbobj;
		  }
		  catch (Exception $e) {
			if ($retry_count<$retry_max) {
			    $retry_count++;
				$retry = true;
			    sleep(1);
				
			}
			else {
			  $retry = false;
			  throw new Exception($e->getMessage()."<HR>".$e->getTraceAsString());
			}  
		  }
		}
		
		

       
		
    }
	
	public function query($str, $resultmode = NULL) {

		if (is_null($resultmode))$resultmode = MYSQLI_STORE_RESULT;
		$res = $this->dbobj->query($str, $resultmode);
		
		if ($res instanceof mysqli_result) {
			
			return new MySQLiResult($res);
			
		}
		
		if ($res === TRUE ) return $res;

		$this->error = $this->dbobj->error;
		return FALSE;

		
	}
	public function free($res) {
		$res->free();
	}

	public function transaction() {
		$ret = $this->dbobj->autocommit(false);
		$this->transaction_in_progress = true;
		register_shutdown_function(array($this, "__shutdown_check"));
	}

	public function __shutdown_check() {
		if ($this->transaction_in_progress) {
		  $this->rollback();
		}
	}

	public function commit() {
		$ret = $this->dbobj->commit();
		$this->transaction_in_progress = false;
	}

	public function rollback() {
		$ret = $this->dbobj->rollback();
		$this->transaction_in_progress = false;
	}
	


	public function dateTime($add_days=0, $interval_type=" DAY ")
	{
		
		$res = $this->query("SELECT DATE_ADD(now(), INTERVAL $add_days $interval_type) as datetime");
		$row = $this->fetch($res);
		return $row["datetime"];
	}
	
	public function numRows($res)
	{
		return $res->num_rows;
	}

	public function numFields($res)
	{
		return $res->field_count;
	}

	public function fieldName($res, $pos)
	{
		$finfo = $this->dbobj->fetch_field_direct($pos);
		return $finfo->name;
	}

	public function fetch($res)
	{

		return $res->fetch_assoc();


	}

	public function fetchArray($res)
	{
		return $res->fetch_assoc();
	}

	public function fetchRow($res)
	{
		return $res->fetch_row();
	}
	
	public function lastID()
	{
			return $this->dbobj->insert_id;
	}
	
	public function escapeString(&$data)
	{
		return $this->dbobj->escape_string($data);
	}
	public function shutdown()
	{

		$this->dbobj->close();


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
		  $res->free();

		  if (!$found) throw new Exception("Field $field_name does not exist in table: $table");
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
		$ret = FALSE;

		$res = $this->query("show tables like '$table' ");
		$row = $res->fetch_row();

		$ret = (is_array($row) && strcmp($row[0],$table)==0) ? TRUE : FALSE;

		return $ret;
	}
}

?>