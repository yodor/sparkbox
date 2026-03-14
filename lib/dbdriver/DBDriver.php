<?php
include_once("dbdriver/DBConnections.php");
include_once("dbdriver/RawResult.php");
include_once("objects/SparkEventManager.php");
include_once("objects/events/DBDriverEvent.php");

abstract class DBDriver
{
    public static function Enum2Array(string $enum_str) : array
    {
        $enum_str = str_replace("enum(", "", $enum_str);
        $enum_str = str_replace(")", "", $enum_str);
        $enum_str = str_replace("'", "", $enum_str);

        return explode(",", $enum_str);
    }

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

    abstract public function hasActiveStatement() : bool;

    abstract public function connect() : void;
    abstract public function disconnect() : void;

    abstract public function isConnected() : bool;

    abstract public function affectedRows() : int;

    abstract public function getError(): string;

    abstract public function query(SQLStatement|string $statement) : true|DBResult;

    abstract public function dateTime(int $add_days = 0, string $interval_type = " DAY ") : string;

    abstract public function timestamp() : int;

    abstract public function lastID(): int;

    abstract public function commit(?string $name = null) : bool;

    abstract public function rollback(?string $name = null) : bool;

    abstract public function transaction(?string $name = null) : bool;

    abstract public function escape(string $data) : string;

    abstract public function queryFields(string $table) : true|DBResult;

    abstract public function tableExists(string $table) : bool;

    abstract public function fieldType(string $table, string $field_name) : string;

    public function __serialize(): array
    {
        return array("connection_name"=>$this->props->getName());
    }
    public function __unserialize(array $data): void
    {
        $this->props = DBConnections::Get($data["connection_name"]);
    }

}