<?php
include_once("dbdriver/DBConnections.php");

abstract class DBDriver
{

    public $PDO = false;

    protected $error = "";

    /**
     * @var DBDriver
     */
    protected static $g_db = NULL;

    public static function Get() : DBDriver
    {
        return static::$g_db;
    }

    public static function Set(DBDriver $connection)
    {
        self::$g_db = $connection;
    }

    public abstract function __construct(DBConnectionProperties $conn, $open_new = true);

    public function __destruct()
    {
        $this->shutdown();
    }

    function close()
    {
    }

    abstract public function query($str);


    abstract public function fetch($str);

    abstract public function fetchRow($str);

    abstract public function fetchArray($str);

    abstract public function dateTime($add_days = 0, $interval_type = " DAY ");

    //return default connection to database
    public static function Factory($open_new = true, $use_persistent = false, $conn_name = "default")
    {

        //DBConnectionProperties
        $conn_props = DBConnections::getConnection($conn_name);

        $currDriver = false;
        switch ($conn_props->driver) {
            case "MySQLi":
                include_once("dbdriver/MySQLiDriver.php");
                $currDriver = new MySQLiDriver($conn_props, $open_new, $use_persistent);
                break;

            case "MySQL":
                include_once("dbdriver/MySQLDriver.php");
                $currDriver = new MySQLDriver($conn_props, $open_new, $use_persistent);
                break;
            case "PDOMySQL":
                include_once("dbdriver/PDOMySQLDriver.php");
                $currDriver = new PDOMySQLDriver($conn_props, $open_new, $use_persistent);
                break;

        }
        return $currDriver;

    }

    public static function create($open_new = true, $use_persistent = false, $conn_name = "default")
    {
        $g_db = DBDriver::Factory($open_new, $use_persistent, $conn_name);
        DBDriver::Set($g_db);
    }

    public function getError()
    {
        return $this->error;
    }

    abstract public function lastID();


    abstract public function commit();

    abstract public function rollback();

    abstract public function transaction();

    abstract public function numRows($res);

    abstract public function numFields($res);

    abstract public function fieldName($res, $pos);

    abstract public function escapeString($data);

    abstract protected function shutdown();

    abstract protected function queryFields($table);

    abstract protected function tableExists($table);

}

?>
