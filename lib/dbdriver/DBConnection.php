<?php
include_once("objects/SparkObject.php");
include_once("dbdriver/DBDriver.php");

class DBConnection extends SparkObject
{
    protected ?DBDriver $driver = null;

    const string DEFAULT_NAME = "default";

    public string $driverClass = "MySQLi";
    public string $database = "";
    public string $user = "";
    public string $pass = "";
    public string $host = "";
    public string $port = "";

    protected array $variables = array();

    protected bool $persistent = false;

    /**
     * Wraps DBDriver and manages connected state
     * @param string $connectionName
     * @param bool $persistent
     */
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

    public function driver(): ?DBDriver
    {
        return $this->driver;
    }

    /**
     * Close driver connection and set instance to null
     * @return void
     */
    public function close() : void
    {
        if (is_null($this->driver)) return;
        if ($this->driver->isConnected()) {
            $this->driver()->disconnect();
        }
        $this->driver = null;
    }

    /**
     * Check if the $this->driver instance is not null and driver->isConnected returns true
     * @return bool
     */
    public function isOpen() : bool
    {
        return !is_null($this->driver) && $this->driver->isConnected();
    }

    /**
     * Opens connection to the database using the specified driverClass.
     * If isOpen is true does nothing.
     * Checks if driver is null and create new instance of driverClass.
     * Calls driver->connect() to restore connection if driver->isConnected() is false.
     * @return void
     * @throws Exception
     */
    public function open() : void
    {
        if ($this->isOpen()) return;

        if (is_null($this->driver)) {
            $this->driver = DBConnections::CreateDriver($this->driverClass, $this);
        }

        if (!$this->driver->isConnected()) {
            $this->driver->connect();
        }
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