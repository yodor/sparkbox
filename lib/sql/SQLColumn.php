<?php
include_once("sql/ISQLGet.php");
include_once("sql/ISQLBinding.php");

class SQLColumn implements ISQLGet, ISQLBinding, IBindingModifier
{
    protected string $prefix = "";

    protected string $alias = "";
    protected string $expression = "";

    protected string $name = "";

    //allow array here to handle multi-insert
    protected array|string|float|int|bool|null $value = null;

    protected string $bindingKey = "";

    protected bool $hasValue = false;
    /**
     * Construct SQLColumn using $name and $value
     * Binding key is created only if $value is passed during the function call
     * * new SQLColumn($name) -> no bindingKey creation or value assigned
     * * new SQLColumn($name, "") -> bindingKey :$name $this->value = "";
     * * new SQLColumn($name, null) -> bindingKey :$name $this->value = null;
     * * new SQLColumn($name, array()) -> bindingKey :$name $this->value = array()
     * * new SQLColumn($name, 3) -> bindingKey :$name $this->value = array()
     * @param string $name
     * @param array|string|float|int|bool|null $value
     * @throws Exception
     */
    public function __construct(string $name)
    {
        if (strlen(trim($name))<1) throw new Exception("SQLColumn name can not be empty");
        $this->name = trim($name);
        $this->bindingKey = "";
        $this->hasValue = false;
        $this->value = null;
        $this->expression = "";
        $this->alias = "";
        $this->prefix = "";
    }

    public function getName() : string
    {
        return $this->name;
    }

    //disable expression
    public function createArray() : void
    {
        $this->value = [];
        $this->nameBindEnable();
    }

    protected function nameBindEnable() : void
    {
        $this->bindingKey = SQLStatement::FormatBindingKey($this->name);
        $this->hasValue = true;
        $this->expression = "";
    }

    protected function nameBindDisable() : void
    {
        $this->bindingKey = "";
        $this->hasValue = false;
        $this->value = null;
    }

    /**
     * Special case for SQLColumn initalized with array() as value
     * Append $value to the array passed in the constructor
     *
     * @param string $value
     * @return void
     * @throws Exception If this column was not initialized with array
     */
    public function addValue(string|float|int|bool|null $value) : void
    {
        if (!is_array($this->value)) throw new Exception("SQLColumn is not initialized with array value");
        $this->value[] = $value;
    }

    public function getValue() : array|string|float|int|bool|null
    {
        if (!$this->hasValue) throw new Exception("SQLColumn has no value");
        return $this->value;
    }

    /**
     * True if this column has received assignment of value
     * @return bool
     */
    public function hasValue() : bool
    {
        return $this->hasValue;
    }

    /**
     * Returns the value for a specific row index.
     * Handles both scalar values and arrays.
     */
    public function getValueAtIndex(int $idx): string|float|int|bool|null
    {
        if (!$this->hasValue) return null;

        if (is_array($this->value)) {
            return $this->value[$idx] ?? null;
        }
        // For single values, we return the value only for the first row (index 0)
        return ($idx === 0) ? $this->value : null;
    }

    public function haveIndex(int $idx) : bool
    {
        if (is_array($this->value) && isset($this->value[$idx])) return true;
        return false;
    }

    public function setAlias(string $alias) : void
    {
        if ($this->bindingKey || $this->hasValue) {
            throw new Exception("SQLColumn[{$this->name}] already have binding key or value");
        }
        $this->alias = trim($alias);
    }

    public function getAlias() : string
    {
        return $this->alias;
    }

    public function setPrefix(string $prefix) : void
    {
        $this->prefix = trim($prefix);
    }

    public function getPrefix() : string
    {
        return $this->prefix;
    }

    /**
     * Configures the column to use a raw SQL expression instead of a simple value.
     * * This method:
     * 1. Disables automatic PDO parameter binding by clearing the bindingKey.
     * 2. Clears any previously set literal value.
     * 3. Sets the raw SQL expression (e.g., "NOW()", "rgt + 2").
     *
     * @param string $expression The raw SQL fragment (e.g., "COUNT(*)", "price + 10").
     * @param string $alias_name Optional alias for column name.
     * @return void
     * @throws Exception If expression is empty
     */
    public function setExpression(string $expression) : void
    {
        $expression = trim($expression);
        if (strlen(trim($expression))<1) throw new Exception("SQLColumn expression can not be empty");

        $this->expression = $expression;
        $this->nameBindDisable();

    }

    public function setValue(string|float|int|bool|null $value) : void
    {
        $this->value = $value;
        $this->nameBindEnable();
    }

    /**
     * Configures a column in the statement fieldset to use a raw SQL expression
     * instead of a simple value with automatic name-derived binding.
     *
     * This method transitions the column to "Manual Mode":
     * * Disables automatic binding for this specific column. ($column->getBindingKey() returns an empty string)
     * * Allows for database-side calculations (arithmetic, functions, subqueries).
     * * Supports manual parameter binding for high-security custom logic.
     * * Clears any previously set literal value.
     * * Sets the raw SQL expression (e.g., "NOW()", "rgt + 2").
     *
     * * Basic usage with SQL functions:
     *
     * $stmt->column("update_date")->set("NOW()");
     * $stmt->column("rgt")->set("rgt + 2");
     *
     * * Advanced usage with manual binding:
     *
     * $stmt->column("rgt")->set("rgt + :value")->bind(":value", 2);
     *
     * @param string $expression The raw SQL fragment (e.g., "NOW()", "rgt + :val").
     * @return self
     * @throws Exception If expression validation
     */
    public function set(string $expression) : SQLColumn
    {
        $this->setExpression($expression);
        return $this;
    }

    public function getExpression() : string
    {
        return $this->expression;
    }

    /**
     * Generates the SQL fragment for this column.
     * * Priority Logic:
     * 1. If an expression exists:
     * - Returns "expression AS alias" if an alias is set (SELECT context).
     * - Returns "prefix.name = expression" if no alias is set (UPDATE/INSERT context).
     * 2. If no expression exists:
     * - Returns "prefix.name AS alias" if an alias is set.
     * - Returns "prefix.name = :bindingKey" for prepared statements (if bindingKey exists).
     * - Returns "prefix.name = value" as a fallback for literal values.
     *
     * @return string The generated SQL fragment.
     * @throws Exception
     */
    public function getSQL() : string
    {

        //base identifier (prefix.name)
        $currentName = ($this->prefix ? $this->prefix . "." : "") . $this->name;

        //PRIORITY: Raw SQL Expression
        if ($this->expression) {
            //SQLColumnSet->setAliasExpression - $select->fields()->setAliasExpression()
            if ($this->alias) {
                return $this->expression . " AS " . $this->alias;
            }
            //SQLStatement column()->set or setExpression()
            //expressions containing :named_parameters are handled using bind()
            return $currentName . " = " . $this->expression;
        }

        //SQLSelect column name
        if (!$this->hasValue) {
            if ($this->alias) {
                return $currentName . " AS " . $this->alias;
            }
            else {
                return $currentName;
            }
        }

        return $currentName . " = " . $this->bindingKey;

    }

    public function getBindingKey() : string
    {
        return $this->bindingKey;
    }

    public function getBindingValue(): string|int|float|bool|null
    {
        if (!$this->bindingKey) throw new Exception("Binding key empty");
        if (!$this->hasValue) throw new Exception("No assigned value");

        if (SQLStatement::IsBindingValueSafe($this->value)) return $this->value;

        throw new Exception("[$this->bindingKey] value is not SQLStatement::IsBoundSafe");
    }


    public function bind(string $bindingKey, float|bool|int|string|null $value): void
    {
        if (!SQLStatement::FormatBindingKey($bindingKey)) throw new Exception("Binding key is not binding safe");
        $this->bindingKey = $bindingKey;
        //we might have expression set already
        $this->hasValue = true;
        $this->value = $value;
    }
}