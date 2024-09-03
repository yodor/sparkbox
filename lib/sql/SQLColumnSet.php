<?php
include_once("sql/SQLColumn.php");

class SQLColumnSet implements ISQLGet
{

    /**
     * @var array of SQLColumn
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
     * Add prefix value to all columns in this collection
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
     * Add direct sql expression to the column collection using alias name as name
     * @param string $expression SQL select expression string
     * @param string $alias Alias name
     * @throws Exception
     */
    public function setExpression(string $expression, string $alias)
    {
        $column = new SQLColumn();
        $column->setExpression($expression, $alias);
        $this->fields[$column->getName()] = $column;
    }

    public function copyTo(SQLColumnSet $other) : void
    {
        foreach ($this->fields as $key => $col) {
            if ($col instanceof SQLColumn) {
                $other->setColumn($col);
            }
        }
    }

    /**
     * Set column names for this collection
     * AS alias is parsed and assigned
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

            $column = new SQLColumn();
            $column->setName($pair[0]);
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

    public function count(): int
    {
        return count(array_keys($this->fields));
    }

    public function getSQL() : string
    {
        $result = array();

        foreach ($this->fields as $key=>$col) {
            if ($col instanceof SQLColumn) {
                $result[] = $col->getSQL();
            }
        }
        return implode(" , ", $result);
    }

}

?>
