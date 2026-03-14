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

    /**
     * Generates the DELETE SQL.
     * Throws an exception if no WHERE conditions are set to prevent accidental full table wipe.
     */
    public function getSQL(): string
    {
        // ЗАЩИТА: Проверка дали имаме поне едно условие
        if ($this->whereset->count() === 0) {
            throw new Exception("Mass DELETE operation blocked: whereset is empty. Provide at least one condition.");
        }

        $sql = $this->type . " FROM " . $this->from;
        $sql .= " WHERE " . $this->whereset->getSQL();

        return $sql;
    }

    public function getBindings(): array
    {
        $bindings = array();
        $this->replaceKeyAppend($bindings, $this->whereset->getBindings());
        $this->replaceKeyAppend($bindings, $this->externalBindings);
        return $bindings;
    }

}