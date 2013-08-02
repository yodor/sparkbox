<?php
include_once("lib/dbdriver/DBConnections.php");

abstract class DBDriver {

	protected $error="";

	public abstract function __construct(DBConnectionProperties $conn, $open_new=true);

	public function __destruct()
	{
		$this->shutdown();
	}
	function close(){	}

	abstract public function query($str);


	abstract public function fetch($str);
	abstract public function fetchRow($str);
	abstract public function fetchArray($str);

	abstract public function dateTime($add_days=0, $interval_type=" DAY ");

	public static function factory($conn_name="default", $open_new=true)
	{
// 			if (self::$currDriver) return self::$currDriver;

			$conn = DBConnections::getConnection($conn_name);



			$currDriver = false;
			switch ($conn->driver)
			{
				case "MySQLi":
						include_once("lib/dbdriver/MySQLiDriver.php");
						$currDriver = new MySQLiDriver($conn, $open_new);
						break;

				case "MySQL":
						include_once("lib/dbdriver/MySQLDriver.php");
						$currDriver = new MySQLDriver($conn, $open_new);
						break;
				case "PGSQL":
						include_once("lib/dbdriver/PGSQLDriver.php");
						$currDriver = new PGSQLDriver($conn, $open_new);
						break;
			}
			return $currDriver;
	}
	public function getError(){
		return $this->error;
	}
	abstract public function lastID();


	abstract public function commit();
	abstract public function rollback();
	abstract public function transaction();
	abstract public function numRows($res);
	abstract public function numFields($res);
	abstract public function fieldName($res,$pos);
	abstract public function escapeString($data);
	abstract protected function shutdown();
	abstract protected function queryFields($table);
	abstract protected function tableExists($table);

}

?>
