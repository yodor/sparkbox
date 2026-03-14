<?php
include_once("dbdriver/RawResult.php");

abstract class DBResult
{
    abstract public function fetch() : ?array;

    abstract public function fetchResult() : ?RawResult;

    abstract public function free() : void;

    /**
     * The number af affected rows during INSERT/UPDATE/DELETE or -1
     * @return int
     */
    abstract public function affectedRows(): int;

    abstract public function fields() : array;

    /**
     * Result is active and ready to fetch more data
     * Return false for INSERT/UPDATE/DELETE
     * @return bool
     */
    abstract public function isActive() : bool;
}