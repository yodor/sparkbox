<?php

class DBConnectionProperties
{
    public const DEFAULT_NAME = "default";

    public $driver = "MySQLi";
    public $database = "";
    public $user = "";
    public $pass = "";
    public $host = "";
    public $port = "";

    protected $variables = array();

    protected $connectionName = DBConnectionProperties::DEFAULT_NAME;

    public function setVariables(array $arr)
    {
        $this->variables = $arr;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function setConnectionName(string $name)
    {
        $this->connectionName = $name;
    }

    public function getConnectionName(): string
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

    public static function haveConnection(string $connection_name): bool
    {
        return array_key_exists($connection_name, self::$available_connections);
    }

    public static function getProperties(string $connection_name): DBConnectionProperties
    {
        if (!self::haveConnection($connection_name)) throw new Exception("Undefined connection name '$connection_name'");
        return self::$available_connections[$connection_name];
    }

    /**
     * Get the default global connection
     * @var DBDriver
     */
    protected static $driver = NULL;

    static public function Get(): DBDriver
    {
        return DBConnections::$driver;
    }

    /**
     * Set the default global connection
     * @param DBDriver $connection
     */
    static public function Set(DBDriver $connection)
    {
        DBConnections::$driver = $connection;
    }

    /**
     * Open new connection to DB
     * @param string $conn_name
     * @param bool $use_persistent
     * @return DBDriver
     * @throws Exception
     */
    static public function Factory($conn_name = DBConnectionProperties::DEFAULT_NAME, $persistent = FALSE): DBDriver
    {
        //DBConnectionProperties
        $props = DBConnections::getProperties($conn_name);

        $currDriver = FALSE;
        switch ($props->driver) {
            case "MySQLi":
                include_once("dbdriver/MySQLiDriver.php");
                $currDriver = new MySQLiDriver($props, $persistent);
                break;

        }
        if ($currDriver instanceof DBDriver) return $currDriver;
        throw new Exception("Unsupoorted DBDriver: " . $props->driver);
    }

}

?>