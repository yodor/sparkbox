<?php

include_once("lib/dbdriver/DBDriver.php");

class PGSQLDriver extends DBDriver
{
	private $connection;

	protected function init()
	{	
		//defined in config/config.php
		global $db_name, $db_user, $db_pass, $db_host, $db_port;

//  		echo "Connection parameters: <br>DB_HOST - $db_host:$db_port<br>DB_USER - $db_user<br>DP_PASS - $db_pass<br>DB_NAME - $db_name ";


		$this->connection = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass options='--client_encoding=UTF8'",PGSQL_CONNECT_FORCE_NEW);


		if (!is_resource($this->connection)) throw new Exception("Unable to connect to database server: ".pg_last_error());

// 		$stat = pg_connection_status($this->connection);
// 		if ($stat === PGSQL_CONNECTION_OK) {
// 			//echo 'Connection status ok';
// 		} else {
// 			throw new Exception("Connection to database is bad");
// 		}    
		
		
	}

	public function dateTime($add_days=0)
	{
		
		$res = $this->query("SELECT now() + INTERVAL '1 day'");
		$row = $this->fetchRow($res);
		return $row[0];
	}
	public function query($str) {

// echo "<hr>";
// $ret = microtime(true);


		$res = pg_query($this->connection, $str) or $this->error=pg_last_error($this->connection);
/*$ret1 =  microtime(true);
echo ($ret1-$ret)." : $str ";
echo "<hr>";	*/	
		return $res;
	}
	public function numRows($res)
	{
		return pg_num_rows($res);
	}
	public function numFields($res){
		return pg_num_fields($res);
	}
	public function fieldName($res,$pos)
	{
		return pg_field_name($res,$pos);
	}
	public function fetch($str){
		if (!is_resource($str)) throw new Exception("No valid resource passed");

		$ret = pg_fetch_assoc($str) or $this->error=pg_last_error($this->connection);
		
		return $ret;
	}
	public function fetchArray($str){
		$ret = pg_fetch_array($str) or $this->error=pg_last_error($this->connection);
		
		return $ret;
	}

	public function fetchRow($str){
		if (!is_resource($str)) throw new Exception("No valid resource passed");

		$ret = pg_fetch_row($str) or $this->error=pg_last_error($this->connection);
// 		if ($ret !== true || !is_array($ret)) throw new Exception(mysql_error());

		return $ret;
	}
	
	public function lastID()
	{
			$r = $this->query("select lastval()");
			$v = $this->fetchRow($r);

			return ($v[0]);
	}
	public function commit(){
		$ret=pg_query($this->connection,"COMMIT")  or $this->error=mysql_error($this->connection);
		return $ret;
	}
	public function rollback(){
		$ret=pg_query($this->connection,"ROLLBACK")  or $this->error=mysql_error($this->connection);
        return $ret;
	}
	public function transaction(){
		$ret=pg_query($this->connection,"START TRANSACTION")  or $this->error=mysql_error($this->connection);
//         $ret=pg_query($this->connection,"BEGIN")  or $this->error=mysql_error($this->connection);
        return $ret;
	}
	public function escapeString(&$data)
	{
		return pg_escape_string($this->connection,$data);
	}
	public function shutdown()
	{

		pg_close($this->connection);


	}
	public function queryFields($table)
	{

		return $this->query("SELECT c.column_name as \"Field\", tc.constraint_type as \"Key\" FROM information_schema.columns c  LEFT JOIN information_schema.constraint_column_usage ccu ON ccu.table_name = c.table_name AND ccu.column_name = c.column_name LEFT JOIN information_schema.table_constraints tc ON tc.constraint_name = ccu.constraint_name WHERE c.table_name ='$table'");
		
	}
	public function tableExists($table)
	{
		$ret = $this->query("select table_name from information_schema.tables where table_name='$table' LIMIT 1");
		if (!$ret) throw new Exception($this->getError());

		$num = $this->numRows($ret);

		if ($num<1) return FALSE;
		return TRUE;
	}
	
}

?>