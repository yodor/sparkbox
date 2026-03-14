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
     * Holds dsn data for creating a connection to DB server
     * @param string $connectionName
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


}