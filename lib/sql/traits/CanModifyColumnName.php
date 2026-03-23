<?php

trait CanModifyColumnName
{

    /**
     * Create new SQLColumn from values in '$columns' parameter and insert/replace each to the internal fieldset collection.
     * * Existing column with the same name will be replaced.
     * * Each value is used as name for the created column (can be prefixed already ie p.status)
     * * If value is in the format "name AS alias" alias is set to the column
     * * Each value is exploded using ',' and used as separate column name
     *
     * Examples:
     *
     * * $stmt->columns("userID", "status") -> add userID and status in the columnset
     * * $stmt->columns("active_status as status, p.prodID", "b.name as aliased") ->
     * add active_status as status, p.prodID, b.name as aliased to the columnset
     *
     * If no names are passed just return the fieldset under the IColumnSetNameModifier interface
     *
     * @param string ...$columns Array of column names/prefixed/aliased to set to this collection
     * @return IColumnSetNameModifier
     * @throws Exception
     */
    public function columns(?string ...$columns) : IColumnSetNameModifier
    {

        if (!$columns || count($columns) < 1) return $this->fieldset;

        //clean empty
        foreach ($columns as $idx=>$columnName) {
            if (!trim($columnName)) {
                unset($columns[$idx]);
            }
        }

        //split by , and trim empty
        foreach ($columns as $idx=>$columnName) {
            $columnMulti = explode(",", $columnName);
            if (count($columnMulti)>0) {
                unset($columns[$idx]);
                foreach ($columnMulti as $columnNameInner) {
                    $columnNameInner = trim($columnNameInner);
                    if ($columnNameInner) $columns[] = trim($columnNameInner);
                }
            }
        }

        foreach ($columns as $columnName) {

            $pair = preg_split("/ as /i", $columnName);

            //no binding key creation no value is set hasValue is false
            $name = $pair[0];
            $alias = $pair[1] ?? "";

            $column = new SQLColumn($name);
            if ($alias) $column->setAlias($alias);

            $this->fieldset->set($column);
        }

        return $this->fieldset;
    }

}