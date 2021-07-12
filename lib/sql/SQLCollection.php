<?php

abstract class SQLCollection
{

    protected $fields = array();

    public function __construct()
    {

    }

    public function copyTo(SQLCollection $other)
    {
        foreach ($this->fields as $key => $val) {
            $other->setValue($key, $val);
        }
    }

    /**
     * Add or replace columns to be selected specified in the $columns
     * @param string ...$columns
     */
    public function set(string ...$columns)
    {
        $this->unset("*");

        foreach ($columns as $name) {
            $name_value = preg_split("/ AS /i", $name);
            $column = $name_value[0];
            $value = "";
            if (isset($name_value[1])) {
                $value = $name_value[1];
            }
            //debug("Column: $column | Value: $value");
            $this->setValue($column, $value);
        }
    }

    public function setValue(string $name, string $value)
    {
        $this->fields[trim($name)] = trim($value);
    }

    /**
     * remvove from the collection all items having value=$remove_value
     * @param string $remove_value
     */
    public function removeValue(string $remove_value)
    {
        foreach ($this->fields as $name => $value) {
            if (strcmp($value, $remove_value)==0) {
                $this->unset($name);
            }
        }
    }

    public function value(string $name): string
    {
        return $this->fields[$name];
    }

    public function isSet(string $name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    public function unset(string $name)
    {
        if ($this->isSet($name)) unset($this->fields[$name]);
    }

    public function reset()
    {
        $this->fields = array();
    }

    public function count(): int
    {
        return count(array_keys($this->fields));
    }

    abstract public function getSQL();
}

?>