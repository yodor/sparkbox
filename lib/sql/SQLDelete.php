<?php
include_once("sql/SQLStatement.php");
include_once("traits/CanSetLimit.php");

class SQLDelete extends SQLStatement
{
    use CanSetLimit;

    static public function Table(string $tableName) : SQLDelete
    {
        $result = new SQLDelete();
        $result->_from->expr($tableName);
        return $result;
    }

    public function __construct(?SQLStatement $other = NULL)
    {
        parent::__construct($other);
        $this->type = "DELETE";
    }

    /**
     * Generates the DELETE SQL.
     * Throws an exception if no WHERE conditions are set to prevent accidental full table wipe.
     */
    public function getSQL(): string
    {
        //fail check atleast one clause
        if ($this->whereset->count() === 0) {
            throw new Exception("Mass DELETE operation blocked: whereset is empty. Provide at least one condition.");
        }

        $sql = $this->type . " FROM " . $this->_from;
        $sql .= " WHERE " . $this->whereset->getSQL();

        $sql .= $this->_limit->getSQL();
        return $sql;
    }

    public function getBindings(): array
    {
        $bindings = array();
        $this->ReplaceKeyAppend($bindings, $this->whereset->getBindings());
        $this->ReplaceKeyAppend($bindings, $this->externalBindings);
        return $bindings;
    }

}