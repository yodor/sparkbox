<?php
include_once("lib/utils/SQLSelect.php");

interface ISelectSource
{

    public function getSelectQuery();

    public function setSelectQuery(SQLSelect $qry);


}

?>