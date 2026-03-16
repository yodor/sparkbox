<?php

trait CanSetColumnNameExpression
{
    /**
     * Configures a column in the statement fieldset to use a raw SQL expression
     * instead of a simple value with automatic name-derived binding.
     *
     * This method transitions the column to "Manual Mode":
     * 1. Disables automatic binding for this specific column.
     * ($column->getBindingKey() returns an empty string)
     * 2. Allows for database-side calculations (arithmetic, functions, subqueries).
     * 3. Supports manual parameter binding for high-security custom logic.
     *
     * * Basic usage with SQL functions:
     *
     * $stmt->setExpression("update_date", "NOW()");
     * $stmt->setExpression("rgt", "rgt + 2");
     *
     * * Advanced usage with manual binding (Prepared Statement):
     *
     * $stmt->setExpression("rgt", "rgt + :value");
     * $stmt->bind(":value", 2);
     *
     * @param string $column_name The name of the column/field to target.
     * @param string $expression The raw SQL fragment (e.g., "NOW()", "rgt + :val").
     * @return void
     * @throws Exception If expression validation fails in SQLColumn.
     */
    public function setExpression(string $column_name, string $expression) : void
    {
        // 1. Initialize a new SQLColumn without a value to prevent
        // the automatic generation of a PDO bindingKey.
        $column = new SQLColumn($column_name);

        // 2. Set the raw SQL expression. Passing an empty string for alias
        // ensures the collectSQL method treats this as an UPDATE/SET assignment.
        $column->setExpression($expression);

        // 3. Register the configured column object into the statement's fieldset
        // so it can be included during the SQL generation process.
        $this->fieldset->setColumn($column);
    }
}