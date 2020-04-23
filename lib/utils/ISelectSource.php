<?php
include_once("lib/utils/SelectQuery.php");

interface ISelectSource
{

    public function getSelectQuery();

    public function setSelectQuery(SelectQuery $qry);


}

?>