<?php
include_once("sql/SQLStatement.php");

class SQLInsert extends SQLStatement
{

    public function __construct()
    {
        parent::__construct();
        $this->type = "INSERT INTO";
    }

    public function getSQL() : string
    {

        $sql = $this->type . " " . $this->from;
        $sql .= "(" . implode(",", array_keys($this->set)) . ")";
        $sql .= " VALUES ";
        $sql .= "(" . implode(",", array_values($this->set)) . ")";

        return $sql;
    }

}
?>
