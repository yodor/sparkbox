<?php
include_once("dbdriver/DBDriver.php");

interface IDBDriverAccess
{
    public function setDB(DBDriver $driver) : void;
    public function getDB() : ?DBDriver;
}