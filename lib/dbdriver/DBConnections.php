<?php

class DBConnectionProperties
{
    public $driver = "MySQLi";
    public $database = "";
    public $user = "";
    public $pass = "";
    public $host = "";
    public $port = "";
    protected $variables = array();

    protected $connectionName = "default";

    public function setVariables($arr)
    {
        $this->variables = $arr;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function setConnectionName($name)
    {
        $this->connectionName = $name;
    }

    public function getConnectionName()
    {
        return $this->connectionName;
    }
}

class DBConnections
{

    public static $conn_count = 0;

    protected static $available_connections = array();
    protected static $active_connections = array();

    public static function addProperties(DBConnectionProperties $dbconn)
    {
        self::$available_connections[$dbconn->getConnectionName()] = $dbconn;
    }

    public static function haveConnection(string $connection_name)
    {
        return array_key_exists($connection_name, self::$available_connections);
    }

    public static function getProperties(string $connection_name)
    {
        if (!self::haveConnection($connection_name)) throw new Exception("Undefined connection '$connection_name'");
        return self::$available_connections[$connection_name];

    }

    /**
     * @var DBDriver
     */
    protected static $driver = NULL;

    static public function Get(): DBDriver
    {
        return DBConnections::$driver;
    }

    static public function Set(DBDriver $connection)
    {
        DBConnections::$driver = $connection;
    }

    //return default connection to database
    static public function Factory($open_new = TRUE, $use_persistent = TRUE, $conn_name = "default")
    {

        //DBConnectionProperties
        $props = DBConnections::getProperties($conn_name);

        $currDriver = FALSE;
        switch ($props->driver) {
            case "MySQLi":
                include_once("dbdriver/MySQLiDriver.php");
                $currDriver = new MySQLiDriver($props, $open_new, $use_persistent);
                break;


        }
        if ($currDriver instanceof DBDriver) return $currDriver;
        throw new Exception("Unsupoorted DBDriver: ".$props->driver);
    }

}

?>
