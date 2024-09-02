<?php
include_once ("objects/SparkObject.php");
include_once ("objects/SparkIterator.php");

interface ISparkCollection {

    public function __clone() : void;
    public function count() : int;
    public function keys() : array;
    public function get(int|string $key) : ?SparkObject;
    public function clear() : void;
    public function toArray() : array;
    public function iterator(): SparkIterator;
    public function contains(SparkObject $object) : bool;
}
?>
