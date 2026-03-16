<?php
include_once("objects/SparkObject.php");
include_once("objects/ISerializable.php");
include_once("objects/IUnserializable.php");

include_once("dbdriver/DBConnections.php");
include_once("dbdriver/RawResult.php");
include_once("objects/SparkEventManager.php");
include_once("objects/events/DBDriverEvent.php");

abstract class DBDriver extends SparkObject implements ISerializable, IUnserializable
{

    protected ?DBConnection $props = null;

    public function __construct(DBConnection $props)
    {
        parent::__construct();
        $this->props = $props;
    }

    public function getConnectionName() : string
    {
        return $this->props->getName();
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Do we have un-fetched result-set waiting ?
     * During nested queries app logic can decide to open additional connection using DBConnections::CreateDriver()
     * @return bool
     */
    abstract public function hasActiveResult() : bool;

    /**
     * Open connection to DB server
     * @return void
     */
    abstract public function connect() : void;

    /**
     * Disconnect from DB server
     * @return void
     */
    abstract public function disconnect() : void;

    /**
     * Is connected
     * @return bool
     */
    abstract public function isConnected() : bool;

    /**
     * Number of affected rows from INSERT/DELETE/UPDATE or -1
     * @return int
     */
    abstract public function affectedRows() : int;

    /**
     * @return string
     */
    abstract public function getError(): string;

    /**
     * Do prepare/execute cycle and return DBResult
     * @param SQLStatement $statement
     * @return DBResult
     */
    abstract public function query(SQLStatement $statement) : DBResult;

    /**
     * Proxy method - Returns the ID of the last inserted row or sequence value
     * @return int
     */
    abstract public function lastID(): int;

    /**
     * Commits a transaction
     * @param string|null $name
     * @return bool
     */
    abstract public function commit(?string $name = null) : void;

    /**
     * Rolls back a transaction
     * @param string|null $name
     * @return bool
     */
    abstract public function rollback(?string $name = null) : void;

    /**
     * @param string|null $name
     * @return bool
     */
    abstract public function transaction(?string $name = null) : void;

    public function __serialize(): array
    {
        return array("connection_name"=>$this->props->getName());
    }

    public function __unserialize(array $data): void
    {
        $this->props = DBConnections::Get($data["connection_name"]);
    }

}