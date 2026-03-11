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
        return $this->collectSQL(false);
    }

    public function collectSQL(bool $do_prepared): string
    {
        $sql = $this->type . " FROM " . $this->from;

        if ($this->whereset->count()>0) {
            $sql .= " WHERE " . $this->whereset->collectSQL($do_prepared);
        }

        return $sql;
    }

    public function getPreparedSQL(): string
    {
        return $this->collectSQL(true);
    }
}