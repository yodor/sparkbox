<?php
include_once("sql/SQLStatement.php");
include_once("sql/SQLSelect.php");

class SQLDelete extends SQLStatement
{

    public function __construct(?SQLStatement $other = NULL)
    {
        parent::__construct($other);
        $this->type = "DELETE";
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
