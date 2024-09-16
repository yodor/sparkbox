<?php
include_once("dbdriver/DBConnections.php");
include_once("dbdriver/RawResult.php");
include_once("objects/SparkEventManager.php");
include_once("objects/events/DBDriverEvent.php");

abstract class DBDriver
{

    public static function Enum2Array($enum_str)
    {
        $enum_str = str_replace("enum(", "", $enum_str);
        $enum_str = str_replace(")", "", $enum_str);
        $enum_str = str_replace("'", "", $enum_str);

        return explode(",", $enum_str);
    }

    abstract function __construct(DBConnection $conn);

    abstract function __destruct();

    abstract public function affectedRows() : int;

    abstract public function getError(): string;

    abstract public function query(string $str);

    abstract public function fetch($result) : ?array;

    abstract public function fetchArray($result) : ?array;

    abstract public function fetchResult($result) : ?RawResult;

    abstract public function dateTime(int $add_days = 0, string $interval_type = " DAY ") : string;

    abstract public function timestamp() : int;

    abstract public function free($result) : void;

    abstract public function lastID(): int;

    abstract public function commit(?string $name = null) : bool;

    abstract public function rollback(?string $name = null) : bool;

    abstract public function transaction(?string $name = null) : bool;

    abstract public function numRows($result): int;

    abstract public function fields($result) : array;

    abstract public function escape(string $data) : string;

    abstract public function queryFields(string $table);

    abstract public function tableExists(string $table);

}

?>
