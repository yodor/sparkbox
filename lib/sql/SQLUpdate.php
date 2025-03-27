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
        $sql = $this->type . " " . $this->from;
        $sql .= " SET ";
        $set = array();
        $names = $this->fieldset->names();
        foreach ($names as $columnName) {
            $column = $this->fieldset->getColumn($columnName);
            $set[] = $column->getSQL();
        }
        $sql .= implode(", ", $set);

        if ($this->whereset->count()>0) {
            $sql.= $this->whereset->getSQL();
        }

        return $sql;
    }

}
