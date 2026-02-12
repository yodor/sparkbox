<?php
include_once("objects/SparkEvent.php");

class DBDriverEvent extends SparkEvent {
    const string OPENED = "opened";
    const string CLOSED = "closed";

    public function __construct(string $name = "", ?SparkObject $source = null)
    {
        parent::__construct($name, $source);
    }
}