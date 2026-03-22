<?php

/**
 * Provides the public set() method for SQLUpdate and SQLInsert that support column value or expression assignment.
 */
trait CanSetColumnValue
{
    /**
     * Create or modify existing SQLColumn in the internal fieldset collection.
     *
     * If value is scalar - the column is configured using automatic name-derived binding for scalar values.
     *
     * If value is IDBValue - the column is configured using expression or expression with binding if bindingKey() is not empty
     *
     * * Manual binding usage:
     * $update->set("rgt", "rgt + :rgt")->bind(":rgt", 3) -> expression column with binding key ":rgt" and binding value 3.
     * $update->set("rgt", new DBExpression("now()")) -> expression column without binding.
     *
     * * Automatic binding
     * $update->set("position", 3) -> value column with name-derived binding -> key ":position" and value 3
     *
     * @param string $columnName
     * @param string|float|int|bool|null $value
     * @return void
     * @throws Exception
     */
    public function set(string $columnName, IDBValue|string|float|int|bool|null $value): void
    {
        $column = $this->fieldset->get($columnName);

        //create new column
        if (is_null($column)) $column = new SQLColumn($columnName);

        //expression column with or without binding
        if ($value instanceof IDBValue) {
            if ($value->bindingKey()) {
                //use binding key as expression directly
                $column->set($value->bindingKey());
                //bind value
                $column->bind($value->bindingKey(), $value->value());
            }
            else {
                //set raw SQL expression and clear binding
                $column->set($value->value());
            }
        }
        //value column
        else {
            //automatic name-derived binding
            $column->setValue($value);
        }
        $this->fieldset->set($column);

    }
}