<?php
include_once("dbdriver/DBConnections.php");
include_once("dbdriver/RawResult.php");
include_once("objects/SparkEventManager.php");
include_once("objects/events/DBDriverEvent.php");

abstract class DBDriver
{

    protected ?DBConnection $props = null;

    public function __construct(DBConnection $props)
    {
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
    abstract public function hasResultSet() : bool;

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
     * Query using raw SQL - only for system stuff
     * @param string $sqlText
     * @return DBResult
     */
    abstract public function queryRaw(string $sqlText) : DBResult;

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
    abstract public function commit(?string $name = null) : bool;

    /**
     * Rolls back a transaction
     * @param string|null $name
     * @return bool
     */
    abstract public function rollback(?string $name = null) : bool;

    /**
     * @param string|null $name
     * @return bool
     */
    abstract public function transaction(?string $name = null) : bool;

    /**
     * Describe table columns using format
     * * Field,Type,Null,Key,Default,Extra
     * * ex Field=>userID , Type=>int(11) unsigned, Null=>NO, Key=>PRI, Default=>NULL, Extra=>auto_increment
     * @param string $tableName
     * @return array<string, array{Field: string, Type:string, Null:string, Key:string, Default:string, Extra:string}>
     * @throws Exception
     */
    abstract public function columnTypes(string $tableName) : array;

    /**
     * Check if a table named - '$tableName' exist in the current connection
     * @param string $tableName
     * @return bool
     */
    abstract public function tableExists(string $tableName) : bool;

    public function __serialize(): array
    {
        return array("connection_name"=>$this->props->getName());
    }
    public function __unserialize(array $data): void
    {
        $this->props = DBConnections::Get($data["connection_name"]);
    }

}