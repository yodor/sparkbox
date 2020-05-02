<?php
include_once("lib/utils/SQLSelect.php");

interface ISelectSource
{

    public function select() : SQLSelect;

    public function setSelect(SQLSelect $qry);

}

?>