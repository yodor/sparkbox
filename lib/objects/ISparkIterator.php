<?php
include_once("objects/SparkObject.php");

interface ISparkIterator
{
    public function reset() : void;
    public function next() : ?SparkObject;
    public function key() : int|string|null;
}