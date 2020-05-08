<?php
include_once("dbdriver/DBConnections.php");

abstract class DBDriver
{

    protected $error = "";

    /**
     * @var DBDriver
     */
    protected static $driver = NULL;

    static public function Get(): DBDriver
    {
        return DBDriver::$driver;
    }

    static public function Set(DBDriver $connection)
    {
        DBDriver::$driver = $connection;
    }

    //return default connection to database
    static public function Factory($open_new = TRUE, $use_persistent = FALSE, $conn_name = "default")
    {

        //DBConnectionProperties
        $conn_props = DBConnections::getConnection($conn_name);

        $currDriver = FALSE;
        switch ($conn_props->driver) {
            case "MySQLi":
                include_once("dbdriver/MySQLiDriver.php");
                $currDriver = new MySQLiDriver($conn_props, $open_new, $use_persistent);
                break;


        }
        if ($currDriver instanceof DBDriver) return $currDriver;
        throw new Exception("Unsupoorted DBDriver: ".$conn_props->driver);
    }

    public static function Enum2Array($enum_str)
    {
        $enum_str = str_replace("enum(", "", $enum_str);
        $enum_str = str_replace(")", "", $enum_str);
        $enum_str = str_replace("'", "", $enum_str);

        return explode(",", $enum_str);
    }

    abstract function __construct(DBConnectionProperties $conn, $open_new = TRUE, $need_persistent = FALSE);

    public function __destruct()
    {
        $this->shutdown();
    }

    public function getError()
    {
        return $this->error;
    }

    abstract public function query(string $str);

    abstract public function fetch($str);

    abstract public function fetchRow($str);

    abstract public function fetchArray($str);

    abstract public function dateTime($add_days = 0, $interval_type = " DAY ");

    abstract public function lastID() : int;

    abstract public function commit();

    abstract public function rollback();

    abstract public function transaction();

    abstract public function numRows($res) : int;

    abstract public function numFields($res) : int;

    abstract public function fieldName($res, int $pos);

    abstract public function escape(string $data);

    abstract public function shutdown();

    abstract public function queryFields(string $table);

    abstract public function tableExists(string $table);

}

?>
