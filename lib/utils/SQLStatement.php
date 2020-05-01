<?php

abstract class SQLStatement
{
    protected $type = "SELECT";
    public $fields = " * ";
    public $from = " ";
    public $where = " ";
    public $group_by = " ";
    public $order_by = " ";
    public $limit = " ";
    public $having = " ";

    public abstract function getSQL($where_only = false);

}

?>