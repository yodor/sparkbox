<?php
include_once("sql/ISQLGet.php");
include_once("sql/ISQLBinding.php");

class SQLColumn implements ISQLGet, ISQLBinding
{
    protected string $prefix = "";

    protected string $alias = "";
    protected string $expression = "";

    protected string $name = "";
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
    public function __construct(string $name, array|string|float|int|bool|null $value = null)
    {
        if (strlen(trim($name))<1) throw new Exception("SQLColumn name can not be empty");

        $this->name = trim($name);

        if (func_num_args() >= 2) {
            //value is provided - create binding key and store the value even if it is empty string
            $this->value = $value;
            $this->bindingKey = SQLStatement::FormatBindingKey($this->name);
            $this->hasValue = true;
        }

    }

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Append $value to this column values
     * If current column value is empty a new array will be created and $value will be appended to it
     * If current column value is already array, $value will be appended to it
     * @param string $value
     * @return void
     */
    public function addValue(string $value) : void
    {
        if (is_array($this->value)) {
            $this->value[] = $value;
            return;
        }

        if ($this->value) {
            $this->value = array($this->value);
        }
        else {
            $this->value = array();
        }

        $this->value[] = $value;
    }

    public function getValue() : array|string
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
    public function getValueAtIndex(int $idx): mixed
    {
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
     * 4. Ensures the column has a valid name or alias for SQL generation.
     *
     * @param string $expression The raw SQL fragment (e.g., "COUNT(*)", "price + 10").
     * @param string $alias_name Optional alias or column name.
     * @return void
     * @throws Exception If expression is empty or if no identity (name/alias) exists.
     */
    public function setExpression(string $expression, string $alias_name = "") : void
    {
        $expression = trim($expression);
        $alias_name = trim($alias_name);

        if (strlen(trim($expression))<1) throw new Exception("SQLColumn expression can not be empty");

        $this->expression = $expression;
        $this->bindingKey = "";
        $this->value = null;
        $this->hasValue = false;

        if ($alias_name) {
            $this->alias = $alias_name;
        }
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
            //SQLStatement setExpression
            //expressions containing :named_parameters are handled using the SQLStatement bind()
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

    public function getBindingValue(): array|string|int|float|bool|null
    {
        if (!$this->bindingKey) throw new Exception("Binding key empty");
        if (!$this->hasValue) throw new Exception("No assigned value");

        if (SQLStatement::IsBoundSafe($this->value)) return $this->value;

        throw new Exception("[$this->bindingKey] value is not SQLStatement::IsBoundSafe");
    }



}