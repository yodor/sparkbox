<?php
include_once("lib/utils/SelectQuery.php");

interface SQLIterator
{
    public function startQuery(SelectQuery $filter = NULL);

    public function haveMoreResults(&$row);

    public function key();

    public function getSelectQuery();

}

?>