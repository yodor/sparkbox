<?php
include_once("objects/SparkObject.php");
include_once("sql/ISQLGet.php");
include_once("sql/ISQLBinding.php");
/**
 * Where clause
 */
class SQLClause extends SparkObject implements ISQLGet, ISQLBinding
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
    protected string $value = "";
    protected string $operator = "";
    protected string $glue = "";

    protected string $bindingKey = "";

    public function __construct(string $operator=SQLClause::DEFAULT_OPERATOR, string $glue=SQLClause::DEFAULT_GLUE)
    {
        parent::__construct();

        $this->glue = SQLClause::DEFAULT_GLUE;
        $this->operator = SQLClause::DEFAULT_OPERATOR;
        $this->bindingKey = "";
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
     * Set the bindingKey as ":$expr" if $value is not empty
     * Custom bindings can be used by calling SQLStatement::bind()
     * @param string $expr
     * @param string $value
     * @param string $operator
     * @return void
     */
    public function setExpression(string $expr, string $value, string $operator = SQLClause::DEFAULT_OPERATOR) : void
    {
        $this->expr = $expr;
        $this->value = $value;
        $this->operator = $operator;
        if ($this->value) {
            $this->bindingKey = ":".$this->expr;
        }

    }

    public function getSQL() : string
    {
        return $this->expr . " " . $this->operator . " " . $this->value;
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

    public function collectSQL(bool $do_prepared): string
    {
        if ($do_prepared) {
            return $this->getPreparedSQL();
        }
        else {
            return $this->getSQL();
        }
    }

    public function getPreparedSQL(): string
    {
        return $this->expr . " " . $this->operator . " " . $this->bindingKey;
    }

    public function getBindingKey() : string
    {
        return $this->bindingKey;
    }

    public function getBindingValue(): string
    {
        if ($this->bindingKey) {
            return $this->value;
        }
        return "";
    }
}