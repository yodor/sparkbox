<?php
include_once("sql/ISQLGet.php");
include_once("sql/IBindingCollection.php");
include_once("sql/SQLColumn.php");

class SQLColumnSet implements ISQLGet, IBindingCollection
{

    /**
     * @var array<string, SQLColumn> column name to SQLColumn collection
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

    public function copyTo(SQLColumnSet $other) : void
    {
        foreach ($this->fields as $name => $col) {
            if (!($col instanceof SQLColumn)) continue;
            $other->setColumn($col);
        }
    }

    public function setColumn(SQLColumn $column) : void
    {
        $this->fields[$column->getName()] = $column;
    }

    public function getColumn(string $name) : SQLColumn
    {
        $name = trim($name);
        if (!isSet($this->fields[$name])) throw new Exception("Column name '$name' not found");
        return $this->fields[$name];
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