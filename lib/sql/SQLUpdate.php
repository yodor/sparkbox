<?php
include_once("sql/SQLStatement.php");
include_once("sql/SQLSelect.php");

class SQLUpdate extends SQLStatement
{

    public function __construct(?SQLStatement $other = NULL)
    {
        parent::__construct($other);
        $this->type = "UPDATE";
    }

    public function getSQL() : string
    {
        return $this->collectSQL(false);
    }

    public function collectSQL(bool $do_prepared): string
    {
        $sql = $this->type . " " . $this->from;
        $sql .= " SET ";
        $set = array();
        $names = $this->fieldset->names();
        foreach ($names as $columnName) {
            $column = $this->fieldset->getColumn($columnName);
            $set[] = $column->collectSQL($do_prepared);
        }
        $sql .= implode(", ", $set);

        if ($this->whereset->count()>0) {
            $sql.= $this->whereset->collectSQL($do_prepared);
        }

        return $sql;
    }

    public function getPreparedSQL(): string
    {
        return $this->collectSQL(true);
    }
}