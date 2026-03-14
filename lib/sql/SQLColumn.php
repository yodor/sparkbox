<?php
include_once("sql/ISQLGet.php");

class SQLColumn implements ISQLGet, ISQLBinding
{
    protected string $prefix = "";

    protected string $alias = "";
    protected string $expression = "";

    protected string $name = "";
    protected array|string $value = "";

    protected string $bindingKey = "";

    /**
     * Construct SQLColumn using $name and $value
     * Binding key is created as :$name if $name && $value are set
     * @param string $name
     * @param array|string $value
     */
    public function __construct(string $name = "", array|string $value = "")
    {
        $this->name = trim($name);
        if (empty($this->name)) {
            throw new Exception("SQLColumn name can not be empty");
        }

        $this->value = $value;

        // Binding key is generated ONLY here during construction
        if ($this->value !== "" || $this->value === 0 || $this->value === "0") {
            $this->bindingKey = SQLStatement::FormatBindingKey($this->name);
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
        return $this->value;
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

    public function setAlias(string $alias) : void
    {
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

        if ($expression === "") {
            throw new Exception("SQL expression must be non-empty strings.");
        }

        $this->expression = $expression;
        $this->bindingKey = "";
        $this->value = "";

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
     * @param bool $do_prepared Whether to use PDO parameter binding placeholders.
     * @return string The generated SQL fragment.
     * @throws Exception
     */
    public function collectSQL(bool $do_prepared) : string
    {
        // 1. Prepare the base identifier (prefix.name)
        $currentName = ($this->prefix ? $this->prefix . "." : "") . $this->name;

        // 2. PRIORITY: Raw SQL Expression
        if ($this->expression) {
            if ($this->alias) {
                return $this->expression . " AS " . $this->alias;
            }
            return $currentName . " = " . $this->expression;
        }

        // 3. CONFLICT CHECK: Validate state consistency
        // A column cannot have an alias (SELECT) and a value/binding (UPDATE) simultaneously.
        if ($this->alias && ($this->bindingKey || !empty($this->value))) {
            throw new Exception("SQLColumn Conflict: Column [{$this->name}] cannot have both an alias and an assigned value.");
        }

        // 4. ALIAS HANDLING (Select Context)
        if ($this->alias) {
            return $currentName . " AS " . $this->alias;
        }

        // 5. MODE BRANCHING: Prepared Statement vs Literal/Direct SQL (Update Context)
        if ($do_prepared) {
            /** --- MODE: PDO Prepared Statement --- **/
            if ($this->bindingKey) {
                return $currentName . " = " . $this->bindingKey;
            }
        }
        else {
            /** --- MODE: Literal SQL (Legacy/Debug/Direct) --- **/
            if (is_array($this->value)) {
                return $currentName . " = " . implode(";", $this->value);
            }

            if (strlen(trim((string)$this->value)) > 0) {
                return $currentName . " = " . $this->value;
            }
        }

        // 6. FINAL FALLBACK: Standard field identifier
        return $currentName;
    }

    public function getSQL() : string
    {
        return $this->collectSQL(false);
    }

    public function getPreparedSQL(): string
    {
        return $this->collectSQL(true);
    }

    public function getBindingKey() : string
    {
        return $this->bindingKey;
    }

    public function getBindingValue(): array|string|int|float|bool|null
    {
        if (!$this->bindingKey) throw new Exception("Binding key empty");

        $bindValue = $this->value;
        if (is_array($this->value)) {
            $bindValue = implode(";", $this->value);
        }

        if (SQLStatement::IsBoundSafe($bindValue)) return $bindValue;

        throw new Exception("[$this->bindingKey] value is not SQLStatement::IsBoundSafe");

    }



}