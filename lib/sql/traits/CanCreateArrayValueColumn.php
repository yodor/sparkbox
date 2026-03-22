<?php

trait CanCreateArrayValueColumn {

    /**
     * Create special column for multi-value inserts. Set the column internal value to array() that can be appended using
     * $column->addValue()
     * @param $column_name
     * @return SQLColumn
     * @throws Exception
     */
    public function columnArray(string $column_name) : IArrayColumn
    {
        $column = new SQLColumn($column_name);
        $column->createArray();
        $this->fieldset->set($column);
        return $column;
    }
}