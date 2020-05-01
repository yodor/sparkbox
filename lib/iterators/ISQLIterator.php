<?php
include_once("lib/utils/SQLSelect.php");

interface ISQLIterator
{

    public function exec() : int;

    public function next();

    public function key() : string;


}

?>