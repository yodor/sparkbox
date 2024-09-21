<?php
include_once("sql/SQLStatement.php");
include_once("sql/SQLSelect.php");

class SQLUpdate extends SQLStatement
{

    public function __construct(SQLStatement $other = NULL)
    {
        parent::__construct($other);
        $this->type = "UPDATE";
    }

    public function getSQL() : string
    {
        $sql = $this->type . " " . $this->from;
        $sql .= " SET ";
        $set = array();
        foreach ($this->set as $columnName => $columnValue) {
            $set[] = $columnName . " = " . $columnValue;
        }
        $sql .= implode(", ", $set);

        if ($this->whereset->count()>0) {
            $sql.= $this->whereset->getSQL();
        }

        return $sql;
    }

}
