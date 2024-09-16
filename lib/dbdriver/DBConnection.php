<?php
include_once("objects/SparkObject.php");
include_once("dbdriver/DBDriver.php");

class DBConnection extends SparkObject
{
    protected ?DBDriver $conn = NULL;

    const string DEFAULT_NAME = "default";

    public string $driver = "MySQLi";
    public string $database = "";
    public string $user = "";
    public string $pass = "";
    public string $host = "";
    public string $port = "";

    protected array $variables = array();

    protected bool $persistent = false;

    public function __construct(string $connectionName=DBConnection::DEFAULT_NAME, bool $persistent=false)
    {
        parent::__construct();
        $this->name = $connectionName;
        $this->persistent = $persistent;
    }

    public function isPersistent(): bool
    {
        return $this->persistent;
    }

    public function setVariables(array $arr) : void
    {
        $this->variables = $arr;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function get(): ?DBDriver
    {
        return $this->conn;
    }

    public function close() : void
    {
        if ($this->conn instanceof DBDriver) {
            $this->conn = null;
        }
    }

    public function isOpen() : bool
    {
        return ($this->conn instanceof DBDriver);
    }

    public function open() : void
    {
        $this->close();

        switch ($this->driver) {
            case "MySQLi":
                include_once("dbdriver/MySQLiDriver.php");
                $this->conn = new MySQLiDriver($this);
                break;
        }
        if (!($this->conn instanceof DBDriver)) throw new Exception("Unsupported DBDriver: " . $this->driver);
    }
}
?>
