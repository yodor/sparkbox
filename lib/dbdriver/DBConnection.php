<?php
include_once("objects/SparkObject.php");

class DBConnection extends SparkObject
{

    const string DEFAULT_NAME = "default";

    public string $driverClass = "PDO";
    public string $database = "";
    public string $user = "";
    public string $pass = "";
    public string $host = "";
    public string $port = "";

    protected array $variables = array();


    /**
     * Wraps DBDriver and manages connected state
     * @param string $connectionName
     * @param bool $persistent
     */
    public function __construct(string $connectionName=DBConnection::DEFAULT_NAME)
    {
        parent::__construct();
        $this->name = $connectionName;
    }

    public function setVariables(array $arr) : void
    {
        $this->variables = $arr;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Serialize empty properties
     * @return array
     */
    public function __serialize(): array
    {
        return array();
    }

    public function __unserialize(array $data):void
    {

    }

}