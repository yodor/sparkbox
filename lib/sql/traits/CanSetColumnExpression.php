<?php

trait CanSetColumnExpression
{
     /**
     * Create/Return named column from the internal fieldset.
     *
     * If column with the specified name does not exist a new column
     * named $column_name will be created and returned with this call.
     *
     * Allows direct access to the SQLColumn methods
     *
     * @param string $column_name
     * @return IExpressionColumn The column with name $column_name created or already existing in the fieldset collection
     * @throws Exception
     */
    public function column(string $column_name) : IExpressionColumn
    {
        $column = $this->fieldset->get($column_name);
        //already exists
        if (!is_null($column)) return $column;

        //create new column no binding - empty column
        $column = new SQLColumn($column_name);
        $this->fieldset->set($column);
        return $column;
    }
}