<?php

/**
 * Provides the public set() method for SQLUpdate and SQLInsert that support column value assignment.
 */
trait CanSetColumnValue
{
    /**
     * Create new SQLColumn using ('$name', '$value') and append to the internal fieldset collection.
     *
     * Usage for simple column = value assignments.
     *
     * SQLColumn state after this call:
     *
     * - bindingKey → ':$name'
     * - value     → '$value'
     * - hasValue  → true
     *
     * Example:
     *   $update->set("p.stock_amount", $amount);
     *   // SQL result: p.stock_amount = :p_stock_amount
     *   // binds ":p_stock_amount" → $amount
     *
     * Also compatible with setExpression():
     *   $update->setExpression("p.stock_amount", "p.stock_amount - 1");
     *   or
     *   $update->setExpression("p.stock_amount", "p.stock_amount - :amount");
     *   $update->bind(":amount", $amount);
     *
     * @param string $name
     * @param string|float|int|bool|null $value
     * @return void
     * @throws Exception
     */
    public function set(string $name, string|float|int|bool|null $value): void
    {
        $column = new SQLColumn($name);
        $column->setValue($value);
        $this->fieldset->setColumn($column);
    }
}