<?php

class DBFactory
{
  private static $connections = array();

  public function __construct()
  {

  }

  public static function connection($name='default')
  {

		global $db_type, $db_name, $db_user, $db_pass, $db_host, $db_port;


		include_once("lib/dbdriver/$db_type.php");

		$retry = true;
		$retry_max = 20;
		$retry_count = 0;

		while ($retry) {
		  try {
					$connection = new $db_type($db_host.":".$db_port, $db_user, $db_pass, $db_name);

					$connection->set_charset("utf8");

					$retry = false;
		  }
		  catch (Exception $e) {
					if ($retry_count<$retry_max) {
							$retry_count++;
							$retry = true;
							sleep(2);

					}
					else {
						$retry = false;


						throw new Exception("Unable to create database connection: ".$e->getMessage());
					}
		  }
		}

		return $connection;
  }

}
?>