<?php
include_once("utils/SQLStatement.php");

class SQLUpdate extends SQLStatement
{
    public $set = array();

    public function __construct(SQLSelect $other = NULL)
    {
        $this->type = "UPDATE ";
        //copy table and where
        if ($other) {
            $this->from = $other->from;
            $this->where = $other->where;
        }
    }

    public function getSQL($where_only = FALSE)
    {
        $sql = $this->type . " " . $this->from;
        $sql .= " SET ";
        $set = array();
        foreach ($this->set as $columnName => $columnValue) {
            $set[] = $columnName . " = " . $columnValue;
        }
        $sql .= implode(",", $set);
        $sql .= " WHERE " . $this->where;

        return $sql;
    }

}