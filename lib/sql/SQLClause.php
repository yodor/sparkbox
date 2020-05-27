<?php

class SQLClause
{
    const DEFAULT_OPERATOR = "=";
    const DEFAULT_GLUE = "AND";

    protected $expr;
    protected $value;
    protected $operator;
    protected $glue;

    public function __construct()
    {
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
}
?>