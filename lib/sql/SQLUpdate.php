<?php
include_once("sql/SQLStatement.php");
include_once("sql/SQLSelect.php");

/**
 * Class SQLUpdate
 * Handles the generation of SQL UPDATE statements.
 * Includes safety checks to prevent accidental mass updates of entire tables.
 */
class SQLUpdate extends SQLStatement
{
    /**
     * SQLUpdate constructor.
     * @param SQLStatement|null $other Optional statement to copy from (e.g., from a SELECT).
     */
    public function __construct(?SQLStatement $other = NULL)
    {
        parent::__construct($other);
        $this->type = "UPDATE";
    }

    /**
     * Generates the complete UPDATE SQL statement.
     * Throws an exception if no WHERE conditions are set to prevent data corruption.
     * * @param bool $do_prepared Whether to generate for a prepared statement.
     * @return string
     * @throws Exception If whereset is empty.
     */
    public function collectSQL(bool $do_prepared): string
    {
        // SAFETY CHECK: Prevent mass updates without a WHERE clause.
        // Updating a whole table by accident is a critical failure.
        if ($this->whereset->count() === 0) {
            throw new Exception("Mass UPDATE operation blocked: whereset is empty. Provide at least one condition.");
        }

        $sql = $this->type . " " . $this->from;
        $sql .= " SET ";

        $set = array();
        $names = $this->fieldset->names();

        foreach ($names as $columnName) {
            $column = $this->fieldset->getColumn($columnName);

            // PRIORITY: Check for raw SQL expressions (e.g., "price = price + 10").
            if ($column->getExpression()) {
                $set[] = ($column->getPrefix() ? $column->getPrefix() . "." : "") .
                    $column->getName() . " = " . $column->getExpression();
            } else {
                // Use column's internal SQL generation (handles prefixes and binding keys).
                $set[] = $column->collectSQL($do_prepared);
            }
        }

        $sql .= implode(", ", $set);

        // Append the WHERE clause (already verified as non-empty above).
        $sql .= " WHERE " . $this->whereset->collectSQL($do_prepared);

        return $sql;
    }

    /**
     * Aggregates all PDO bindings from the SET fields and WHERE conditions.
     * Ensures only the first value (index 0) is used for updates if arrays are present.
     * * @return array
     */
    public function getBindings(): array
    {
        $bindings = array();
        $names = $this->fieldset->names();

        // 1. Process SET clause bindings
        foreach ($names as $name) {
            $column = $this->fieldset->getColumn($name);
            $key = $column->getBindingKey();

            if ($key) {
                // For UPDATE, we consistently use the first value (index 0).
                // This prevents issues if a multi-insert array was passed to the object.
                $bindings[$key] = $column->getValueAtIndex(0);
            }
        }

        // 2. Merge bindings from the WHERE clause.
        $this->replaceKeyAppend($bindings, $this->whereset->getBindings());

        // 3. Merge manual external bindings from bind() calls.
        $this->replaceKeyAppend($bindings, $this->externalBindings);

        return $bindings;
    }

    public function getSQL(): string
    {
        return $this->collectSQL(false);
    }

    public function getPreparedSQL(): string
    {
        return $this->collectSQL(true);
    }
}