<?php

trait CanCreateArrayValueColumn {

    /**
     * Create special column for multi-value inserts. Set the column internal value to array() that can be appended using
     * $column->addValue()
     * @param $column_name
     * @return SQLColumn
     * @throws Exception
     */
    public function columnArray(string $column_name) : SQLColumn
    {
        $column = new SQLColumn($column_name);
        $column->createArray();
        $this->fieldset->setColumn($column);
        return $column;
    }
}