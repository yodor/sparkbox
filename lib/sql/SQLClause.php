<?php
include_once("objects/SparkObject.php");
include_once("sql/ISQLGet.php");
include_once("sql/ISQLBinding.php");
include_once("sql/IBindingModifier.php");

/**
 * Where clause
 */
class SQLClause extends SparkObject implements ISQLGet, ISQLBinding, IBindingModifier
{
    /**
     * Connect the value to the expression using this operator by default
     */
    const string DEFAULT_OPERATOR = "=";

    /**
     * Connect this clause to the rest of the clauses using this operator by default
     */
    const string DEFAULT_GLUE = "AND";

    protected string $expr = "";

    protected array|string|float|int|bool|null $value = null;

    protected string $operator = SQLClause::DEFAULT_OPERATOR;

    protected string $glue = SQLClause::DEFAULT_GLUE;

    protected string $bindingKey = "";

    protected bool $hasValue = false;

    /**
     * Create empty SQLClause
     * Operator is set to SQLClause::DEFAULT_OPERATOR -> "="
     * Glue is set to SQLClause::DEFAULT_GLUE -> "AND"
     * In this state no string will be returned from getSQL()
     */
    public function __construct()
    {
        parent::__construct();

        $this->glue = SQLClause::DEFAULT_GLUE;
        $this->operator = SQLClause::DEFAULT_OPERATOR;
        $this->bindingKey = "";
        $this->hasValue = false;
        $this->expr = "";
    }

    public function setGlue(string $glue) : void
    {
        $this->glue = $glue;
    }

    public function getGlue(): string
    {
        return $this->glue;
    }

    /**
     * Set clause expression to '$expr'.
     * * if '$value' is passed as parameter (even if is null or empty string) - create automatic bindingKey :Hash($expr)
     * * if only $expr is passed as parameter - clear the operator - set to ""
     *
     * Custom bindings can be used when no '$value' is passed to this call by calling the bind() method
     * ex: $clause->setExpression("name LIKE :name");
     * $statement->bind(":name", $name);
     *
     * @param string $expr
     * @param array|string|float|int|bool|null $value
     * @param string $operator
     * @return void
     * @throws Exception
     */
    public function setExpression(string $expr, array|string|float|int|bool|null $value = null) : void
    {
        if (strlen(trim($expr)) == 0) throw new Exception("Invalid expression");

        $this->expr = $expr;
        $this->hasValue = false;

        if (func_num_args() >= 2) {
            //value is provided - create binding key and store the value even if it is empty string
            $this->value = $value;
            $this->bindingKey = SQLStatement::FormatBindingKey(Spark::Hash($expr));
            $this->hasValue = true;
        }
        else {
            //clear the operator - no value not used actually as hasValue is false
            $this->operator = "";
        }
    }

    public function bind(string $bindingKey, string|float|int|bool|null $value) : void
    {
        if (!SQLStatement::IsBindingKeySafe($bindingKey)) throw new InvalidArgumentException("Binding key incorrect");
        if (!SQLStatement::IsBindingValueSafe($value)) throw new InvalidArgumentException("Binding value incorrect");
        $this->bindingKey = $bindingKey;
        $this->value = $value;
        $this->hasValue = true;
    }

    public function getExpression() : string
    {
        return $this->expr;
    }

    public function getValue() : string
    {
        return $this->value;
    }

    public function getOperator() : string
    {
        return $this->operator;
    }
    public function setOperator(string $operator) : void
    {
        $this->operator = $operator;
    }

    /**
     * Return prepared statement ready SQL
     * If expr is empty but the bind() method was used to create bindings,
     * might be used as transient binding only to the parent statement
     * @return string
     */
    public function getSQL(): string
    {
        if ($this->hasValue && $this->expr) {
            return $this->expr . " " . $this->operator . " " . $this->bindingKey;
        }
        //might return transient bindings if $this->expr is empty and there are binding added.
        //ie clause is empty but contains bindings
        return $this->expr;
    }


    public function getBindingKey() : string
    {
        return $this->bindingKey;
    }

    public function getBindingValue(): string|int|float|bool|null
    {
        if (!$this->bindingKey) throw new Exception("Binding key is empty");

        if (SQLStatement::IsBindingValueSafe($this->value)) return $this->value;

        throw new Exception("[$this->bindingKey] value is not SQLStatement::IsBoundSafe");

    }
}