<?php
include_once("objects/SparkObject.php");
include_once("sql/ISQLGet.php");
include_once("sql/IBindingCollection.php");
include_once("sql/column/SQLColumn.php");
include_once("sql/column/IColumnSetNameModifier.php");

class SQLColumnSet extends SparkObject implements ISQLGet, IBindingCollection, IColumnSetNameModifier
{

    /**
     * @var array<string, SQLColumn> column prefixed name to SQLColumn map
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

    public function copyTo(IColumnSetNameModifier $other) : void
    {
        foreach ($this->fields as $name => $col) {
            if (!($col instanceof SQLColumn)) continue;
            $other->set($col);
        }
    }

    /**
     * Insert/Replace into the collection using SQLColumn::getNamePrefix() as key
     *
     * @param SQLColumn $column
     * @return void
     */
    public function set(SQLColumn $column) : void
    {
        $column->setParent($this);
        $this->fields[$column->getNamePrefix()] = $column;
    }

    /**
     * Search for $prefixedName and return if found
     *
     * @param string $prefixedName
     * @return SQLColumn|null
     */
    public function get(string $prefixedName) : ?SQLColumn
    {
        return $this->fields[$prefixedName] ?? null;
    }

    public function isSet(string $prefixedName): bool
    {
        $name = trim($prefixedName);
        return array_key_exists($prefixedName, $this->fields);
    }

    public function unset(string $prefixedName) : void
    {
        $prefixedName = trim($prefixedName);
        if ($this->isSet($prefixedName)) unset($this->fields[$prefixedName]);
    }

    public function reset() : void
    {
        $this->fields = array();
    }

    public function count(): int
    {
        return count(array_keys($this->fields));
    }

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


    public function setPrefix(string $prefix): void
    {
        foreach ($this->fields as $prefixedName => $column) {

            if (!$column) continue;

            if ($column->getExpression()) continue;

            $this->unset($prefixedName);

            //clear/set the prefix
            $column->setPrefix($prefix);

            //set again
            $this->set($column);
        }
    }

    public function clearPrefix(): void
    {
        $this->setPrefix("");
    }
}