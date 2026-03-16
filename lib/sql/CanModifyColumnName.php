<?php

trait CanModifyColumnName
{

    /**
     *
     * Set prefix to all column names in the fieldset
     *
     * In effect each column name will be '$prefix.columnName'
     *
     * @param string $prefix the prefix to use
     * @throws Exception
     */
    public function setPrefix(string $prefix) : void
    {
        foreach ($this->fieldset->names() as $idx => $name) {
            $column = $this->fieldset->getColumn($name);
            $column->setPrefix($prefix);
        }
    }

    /**
     * Clear prefix from the column names in the fieldset
     *
     * @return void
     * @throws Exception
     */
    public function clearPrefix() : void
    {
        $this->setPrefix("");
    }

    /**
     * Proxy method to check if column name is inside the fieldset collection
     * @param string $name
     * @return bool
     */
    public function isSet(string $name): bool
    {
        return $this->fieldset->isSet($name);
    }

    /**
     * Proxy method fieldset->unset() remove column name from the fieldset
     * @param string $name
     */
    public function unset(string $name) : void
    {
        $this->fieldset->unset($name);
    }

    /**
     * Proxy method fieldset->reset() remove all columns
     *
     * @return void
     */
    public function reset() : void
    {
        $this->fieldset->reset();
    }

    /**
     * Create new SQLColumn from values in '$columns' parameter and append to the internal fieldset collection.
     * * Existing column with the same name is replaced.
     * * Each value is used as name for the created column.
     * * If value is in the format "name AS alias" alias is set to the column
     *
     * @param string ...$columns Array of column names to set to this collection
     * @throws Exception
     */
    public function set(string ...$columns) : void
    {
        $this->fieldset->unset("*");

        foreach ($columns as $item) {
            if (!(trim($item)))continue;
            $pair = preg_split("/ as /i", $item);

            //no binding key creation no value is set hasValue is false
            $column = new SQLColumn($pair[0]);
            if (isset($pair[1])) {
                $column->setAlias($pair[1]);
            }

            $this->fieldset->setColumn($column);
        }
    }

    /**
     * Proxy method fieldset->names() - Return column names currently set in the filedset
     * @return array<string>
     */
    public function columnNames() : array
    {
        return $this->fieldset->names();
    }
}