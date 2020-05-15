<?php
include_once("utils/SQLSelect.php");

interface ISelectSource
{

    public function select(): SQLSelect;

    public function setSelect(SQLSelect $qry);

}

?>