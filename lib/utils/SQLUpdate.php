<?php
include_once ("utils/SQLStatement.php");

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
        $sql = $this->type." ".$this->from;
        $sql.=" SET ";
        foreach ($this->set as $columnName=>$columnValue) {
            $sql.=$columnName." = ".$columnValue;
        }
        $sql.= " WHERE ".$this->where;

        return $sql;
    }

    public function combine(SQLSelect $filter)
    {
        if ($filter->where) {
            if ($this->where) {
                $this->where.= " AND ";
            }
            $this->where.= $filter->where;
        }
    }
}