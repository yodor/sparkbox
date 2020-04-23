<?php

class DBConnectionProperties
{
    public $driver = "MySQL";
    public $database = "";
    public $user = "";
    public $pass = "";
    public $host = "";
    public $port = "";
    protected $variables = array();

    public $is_pdo = false;

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

    protected static $available_connections = array();
    protected static $active_connections = array();

    public static function addConnection(DBConnectionProperties $dbconn)
    {
        self::$available_connections[$dbconn->getConnectionName()] = $dbconn;
    }

    public static function haveConnection($connection_name)
    {
        return array_key_exists($connection_name, self::$available_connections);
    }

    public static function getConnection($connection_name)
    {
        if (!self::haveConnection($connection_name)) throw new Exception("Undefined connection '$connection_name'");
        return self::$available_connections[$connection_name];

    }

}

?>
