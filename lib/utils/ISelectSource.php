<?php
include_once("sql/SQLSelect.php");

interface ISelectSource
{

    public function select(): SQLSelect;

    public function setSelect(SQLSelect $qry);

}

?>