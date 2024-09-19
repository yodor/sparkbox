<?php
include_once("sql/SQLStatement.php");
include_once("sql/SQLSelect.php");

class SQLDelete extends SQLStatement
{

    public function __construct(SQLSelect $other = NULL)
    {
        parent::__construct();
        $this->type = "DELETE";

        //copy table and where
        if ($other) {
            $this->from = $other->from;
            $other->where()->copyTo($this->whereset);
        }
    }

    public function getSQL() : string
    {
        $sql = $this->type . " FROM " . $this->from;

        if ($this->whereset->count()>0) {
            $sql .= $this->whereset->getSQL();
        }

        return $sql;
    }

}
