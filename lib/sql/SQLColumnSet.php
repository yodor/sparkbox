<?php
include_once("sql/SQLColumn.php");

class SQLColumnSet implements ISQLGet, IBindingCollection
{

    /**
     * @var array SQLColumn collection
     */
    protected array $fields = array();


    public function __construct()
    {

    }

    public function __clone()
    {
        foreach ($this->fields as $name => $col) {
            $this->fields[$name] = clone $col;
        }
    }

    /**
     * Set prefix to all columns in this collection
     * Effectively during getSQL of column it would return $prefix.col_name
     * @param string $prefix the prefix to add
     */
    public function setPrefix(string $prefix) : void
    {
        foreach ($this->fields as $name => $col) {
            if ($col instanceof SQLColumn) {
                $col->setPrefix($prefix);
            }
        }
    }

    /**
     * Clear prefix value from the columns in this collection
     * @return void
     */
    public function clearPrefix() : void
    {
        foreach ($this->fields as $name => $col) {
            if ($col instanceof SQLColumn) {
                $col->setPrefix("");
            }
        }
    }

    /**
     * Add direct sql expression to the column collection using '$alias_name' as the column name.
     *
     * Existing column named '$alias_name' would be replaced with the newly created column.
     *
     * No automatic binding is created.
     *
     * @param string $expression SQL select expression string
     * @param string $alias_name Alias name
     * @throws Exception
     */
    public function setExpression(string $expression, string $alias_name) : void
    {
        // Using trim to ensure we don't accept strings with only whitespace
        $expression = trim($expression);
        $alias_name = trim($alias_name);

        if ($expression === "" || $alias_name === "") {
            throw new Exception("SQL expression and alias must be non-empty strings.");
        }

        $column = new SQLColumn($alias_name);
        $column->setExpression($expression, $alias_name);
        $this->fields[$alias_name] = $column;
    }

    public function copyTo(SQLColumnSet $other) : void
    {
        foreach ($this->fields as $name => $col) {
            if (!($col instanceof SQLColumn)) continue;
            $other->setColumn($col);
        }
    }

    /**
     * Set column names for this collection
     * 'AS' alias is parsed and assigned ie 'column1 as column' will create aliased SQLColumn
     *
     * @param string ...$columns Array of column names to set to this collection
     * @throws Exception
     */
    public function set(string ...$columns) : void
    {
        $this->unset("*");

        foreach ($columns as $item) {
            if (!(trim($item)))continue;
            $pair = preg_split("/ as /i", $item);

            $column = new SQLColumn($pair[0]);
            if (isset($pair[1])) {
                $column->setAlias($pair[1]);
            }

            $this->fields[$column->getName()] = $column;
        }
    }

    public function setColumn(SQLColumn $column) : void
    {
        $this->fields[$column->getName()] = $column;
    }

    public function getColumn(string $name) : SQLColumn
    {
        if (!isSet($this->fields[$name])) throw new Exception("Column name '$name' not found");
        return $this->fields[$name];
    }

    public function isSet(string $name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    public function unset(string $name) : void
    {
        if ($this->isSet($name)) unset($this->fields[$name]);
    }

    public function reset() : void
    {
        $this->fields = array();
    }

    /**
     * Return the number of assigned columns in this collection
     * @return int
     */
    public function count(): int
    {
        return count(array_keys($this->fields));
    }

    /**
     * Return the column names in this collection
     * @return array
     */
    public function names() : array
    {
        return array_keys($this->fields);
    }

    /**
     * Return the values of the columns as column_name=>column_value
     *
     * @return array
     */
    public function values() : array
    {
        $result = array();
        foreach ($this->fields as $name => $col) {
            if (!($col instanceof SQLColumn)) continue;
            $result[$name] = $col->getValue();
        }
        return $result;
    }

    public function collectSQL(bool $do_prepared) : string
    {
        $result = array();
        foreach ($this->fields as $name=>$col) {
            if (!($col instanceof SQLColumn)) continue;
            $result[] = $col->collectSQL($do_prepared);
        }
        return implode(" , ", $result);
    }

    public function getSQL() : string
    {
        return $this->collectSQL(false);
    }

    public function getPreparedSQL(): string
    {
        return $this->collectSQL(true);
    }

    public function getBindings(): array
    {
        $result = array();
        foreach ($this->fields as $name => $col) {
            if (!($col instanceof ISQLBinding)) continue;
            $bindingKey = $col->getBindingKey();
            if (!$bindingKey) continue;

            $value = $col->getBindingValue();
            if (SQLStatement::IsBoundSafe($value)) {
                $result[$bindingKey] = $value;
            }
            else throw new Exception("[$bindingKey] value is not SQLStatement::IsBoundSafe");
        }
        return $result;
    }

    /**
     * Calculates the total number of rows based on the column with the most values.
     */
    public function getRowCount(): int
    {
        $maxRows = 0;
        foreach ($this->fields as $column) {
            $val = $column->getValue();
            if (is_array($val)) {
                $maxRows = max($maxRows, count($val));
            } elseif ($val !== "" || $val === 0 || $val === "0") {
                $maxRows = max($maxRows, 1);
            }
        }
        return $maxRows;
    }
}