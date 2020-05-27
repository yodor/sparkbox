<?php
include_once("sql/SQLStatement.php");
include_once("sql/SQLSelect.php");

class SQLUpdate extends SQLStatement
{
    protected $set = array();

    public function __construct(SQLSelect $other = NULL)
    {
        parent::__construct();

        $this->type = "UPDATE";

        //copy the where clause collection
        if ($other) {

            $this->from = $other->from;
            $other->where()->copyTo($this->whereset);
        }
    }

    public function set(string $column, string $value) {
        $this->set[$column] = $value;
    }

    public function getSQL($where_only = FALSE)
    {
        $sql = $this->type . " " . $this->from;
        $sql .= " SET ";
        $set = array();
        foreach ($this->set as $columnName => $columnValue) {
            $set[] = $columnName . " = " . $columnValue;
        }
        $sql .= implode(", ", $set);

        if ($this->whereset->count()>0) {
            $sql.= $this->whereset->getSQL(true);
        }


        return $sql;
    }

}