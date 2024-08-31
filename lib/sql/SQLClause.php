<?php
include_once("objects/SparkObject.php");

class SQLClause extends SparkObject
{
    /**
     * Connect the value to the expression using this operator by default
     */
    const DEFAULT_OPERATOR = "=";

    /**
     * Connect this clause to the rest of the clauses using this operator by default
     */
    const DEFAULT_GLUE = "AND";

    protected string $expr = "";
    protected string $value = "";
    protected string $operator = "";
    protected string $glue = "";

    public function __construct(string $operator=SQLClause::DEFAULT_OPERATOR, string $glue=SQLClause::DEFAULT_GLUE)
    {
        parent::__construct();

        $this->glue = SQLClause::DEFAULT_GLUE;
        $this->operator = SQLClause::DEFAULT_OPERATOR;
    }

    public function setGlue(string $glue)
    {
        $this->glue = $glue;
    }

    public function getGlue(): string
    {
        return $this->glue;
    }

    public function setExpression(string $expr, string $value, string $operator = SQLClause::DEFAULT_OPERATOR)
    {
        $this->expr = $expr;
        $this->value = $value;
        $this->operator = $operator;
    }

    public function getSQL(): string
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
}
?>
