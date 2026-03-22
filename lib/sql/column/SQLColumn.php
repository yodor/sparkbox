<?php
include_once("sql/ISQLGet.php");
include_once("sql/ISQLBinding.php");
include_once("sql/column/IAliasedColumn.php");
include_once("sql/column/IArrayColumn.php");
include_once("sql/column/IExpressionColumn.php");

class SQLColumn extends SparkObject implements ISQLGet, ISQLBinding, IBindingModifier, IAliasedColumn, IArrayColumn, IExpressionColumn
{
    protected string $prefix = "";

    protected string $alias = "";
    protected string $expression = "";

    //allow array here to handle multi-insert
    protected array|string|float|int|bool|null $value = null;

    protected string $bindingKey = "";

    protected bool $hasValue = false;

    /**
     * Construct new empty SQLColumn using name \$name.
     *
     * \$name is checked using InputSanitizer::SafeSQLColumn for validity
     *
     * @param string $name
     * @throws Exception if \$name is not safe sql column name
     */
    public function __construct(string $name)
    {
        parent::__construct();

        $name = trim($name);
        if (strlen($name)<1) throw new Exception("SQLColumn name can not be empty");

        $dotPos = strpos($name, '.');
        $prefix = ($dotPos !== false) ? trim(substr($name, 0, $dotPos)) : "";
        $name = ($dotPos !== false) ? trim(substr($name, $dotPos + 1)) : $name;

        if (!InputSanitizer::SafeSQLColumn($name, false)) throw new Exception("Incorrect column name: $name");

        $this->name = $name;
        $this->prefix = $prefix;

        $this->bindingKey = "";
        $this->hasValue = false;
        $this->value = null;
        $this->expression = "";
        $this->alias = "";

    }

    /**
     * Configure this column as array values column - used from insert statement to do multi-value inserts
     *
     * Enables the automatic name-derived bindingKey
     *
     * @return void
     */
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
     * Special case for SQLColumn configured using createArray() method.
     *
     * Append $value to the internal value array.
     *
     * @param string|float|int|bool|null $value
     * @return void
     * @throws Exception If this column was not initialized with createArray() method
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
     * Get the full name using the prefix
     * @return string
     */
    public function getNamePrefix() : string
    {
        return ($this->prefix ? $this->prefix."." : "").$this->name;
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
     * Re/Configure this column as 'value based' column and enable the automatic name-derived bindingKey.
     *
     * @param string|float|int|bool|null $value
     * @return void
     */
    public function setValue(string|float|int|bool|null $value) : void
    {
        $this->value = $value;
        $this->nameBindEnable();
    }

    /**
     * Re/Configures the column as 'expression based' column and disable automatic name-derived bindingKey.
     *
     * Clears any previously set literal value and assigns the expression string.
     *
     * If \$expression contains named parameter like :value their value should be bonded separately using call to bind()
     *
     * Allows database-side calculations (arithmetic, functions, subqueries) e.g. "NOW()", "rgt + 2", "rgt + :value"
     *
     * Usage without binding - plain SQL text:
     *
     * * $stmt->column("update_date")->set("NOW()");
     * * $stmt->column("rgt")->set("rgt + 2");
     *
     * Usage with named parameter and binding:
     *
     * * $stmt->column("rgt")->set("rgt + :value")->bind(":value", 2);
     * * $stmt->column("rgt")->set(":value")->bind(":value", 2);
     *
     * @param string $expression The raw SQL fragment (e.g., "COUNT(*)", "NOW()", "price + 10")
     * @return self
     * @throws Exception If expression is empty
     */
    public function set(string $expression) : SQLColumn
    {
        $expression = trim($expression);
        if (strlen(trim($expression))<1) throw new Exception("SQLColumn expression can not be empty");

        $this->expression = $expression;
        $this->nameBindDisable();
        return $this;
    }

    public function getExpression() : string
    {
        return $this->expression;
    }

    /**
     * Generates the SQL fragment for this column.
     *
     * * expression is set and no alias is active
     * -> prefix.name = expression
     * * expression is set and alias is active
     * -> expression AS alias (SQLSelect)
     * * value is empty and alias is present
     * -> prefix.name AS alias
     * * value is empty and alias is not set
     * -> prefix.name
     * * else binding is set
     * -> prefix.name = bindingKey
     *
     * @return string The generated SQL fragment.
     * @throws Exception
     */
    public function getSQL() : string
    {

        //base identifier (prefix.name)
        $currentName = $this->getNamePrefix();

        //PRIORITY: Raw SQL Expression
        if ($this->expression) {
            //SQLColumnSet->setAliasExpression
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
                //empty value column or plain select column name
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
        if (!SQLStatement::IsBindingKeySafe($bindingKey)) throw new Exception("Binding key is not binding safe");
        $this->bindingKey = $bindingKey;
        //we might have expression set already
        $this->hasValue = true;
        $this->value = $value;
    }
}