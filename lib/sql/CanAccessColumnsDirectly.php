<?php

trait CanAccessColumnsDirectly
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
     * @return SQLColumn The column with name $column_name created or already existing in the fieldset collection
     * @throws Exception
     */
    public function column(string $column_name) : SQLColumn
    {
        if ($this->fieldset->isSet($column_name)) {
            return $this->fieldset->getColumn($column_name);
        }

        //no binding - empty column
        $column = new SQLColumn($column_name);
        $this->fieldset->setColumn($column);
        return $column;
    }
}