<?php
include_once("dbdriver/RawResult.php");

abstract class DBResult
{
    abstract public function fetch() : ?array;

    abstract public function fetchResult() : ?RawResult;

    abstract public function free() : void;

    abstract public function numRows(): int;

    abstract public function fields() : array;
}
?>