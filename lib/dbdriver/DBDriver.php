<?php
include_once("dbdriver/DBConnections.php");
include_once("dbdriver/RawResult.php");

abstract class DBDriver
{

    public static function Enum2Array($enum_str)
    {
        $enum_str = str_replace("enum(", "", $enum_str);
        $enum_str = str_replace(")", "", $enum_str);
        $enum_str = str_replace("'", "", $enum_str);

        return explode(",", $enum_str);
    }

    abstract function __construct(DBConnectionProperties $conn, bool $persistent = FALSE);

    public function __destruct()
    {
        $this->shutdown();
    }

    abstract public function affectedRows() : int;

    abstract public function getError(): string;

    abstract public function query(string $str);

    abstract public function fetch($str) : ?array;

    abstract public function fetchArray($str) : ?array;

    abstract public function fetchResult($str) : ?RawResult;

    abstract public function dateTime($add_days = 0, $interval_type = " DAY ");

    abstract public function timestamp() : int;

    abstract public function lastID(): int;

    abstract public function commit();

    abstract public function rollback();

    abstract public function transaction();

    abstract public function numRows($res): int;

    abstract public function numFields($res): int;

    abstract public function fieldName($res, int $pos);

    abstract public function escape(string $data) : string;

    abstract public function shutdown();

    abstract public function queryFields(string $table);

    abstract public function tableExists(string $table);

}

?>
