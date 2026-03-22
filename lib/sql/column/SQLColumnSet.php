<?php
include_once("objects/SparkObject.php");
include_once("sql/ISQLGet.php");
include_once("sql/IBindingCollection.php");
include_once("sql/column/SQLColumn.php");

class SQLColumnSet extends SparkObject implements ISQLGet, IBindingCollection
{

    /**
     * @var array<string, SQLColumn> column name to SQLColumn collection
     */
    protected array $fields = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function __clone()
    {
        foreach ($this->fields as $name => $col) {
            $this->fields[$name] = clone $col;
        }
    }

    public function copyTo(SQLColumnSet $other) : void
    {
        foreach ($this->fields as $name => $col) {
            if (!($col instanceof SQLColumn)) continue;
            $other->set($col);
        }
    }

    /**
     * Assign to the collection using getNamePrefix as key
     * @param SQLColumn $column
     * @return void
     */
    public function set(SQLColumn $column) : void
    {
        $column->setParent($this);
        $this->fields[$column->getNamePrefix()] = $column;
    }

    public function get(string $name) : ?SQLColumn
    {
        return $this->fields[$name] ?? null;
    }

    public function isSet(string $name): bool
    {
        $name = trim($name);
        return array_key_exists($name, $this->fields);
    }

    public function unset(string $name) : void
    {
        $name = trim($name);
        if ($this->isSet($name)) unset($this->fields[$name]);
    }

    /**
     * Clear/empty this columnset
     * @return void
     */
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

    public function getSQL() : string
    {
        $result = array();
        foreach ($this->fields as $name=>$col) {
            if (!($col instanceof SQLColumn)) continue;
            $result[] = $col->getSQL();
        }
        return implode(" , ", $result);
    }

    public function getBindings(): array
    {
        $result = array();
        foreach ($this->fields as $name => $col) {
            if (!($col instanceof ISQLBinding)) continue;
            $bindingKey = $col->getBindingKey();
            if (!$bindingKey) continue;

            $value = $col->getBindingValue();
            if (SQLStatement::IsBindingValueSafe($value)) {
                $result[$bindingKey] = $value;
            }
            else throw new Exception("[$bindingKey] value is not SQLStatement::IsBoundSafe");
        }
        return $result;
    }


}