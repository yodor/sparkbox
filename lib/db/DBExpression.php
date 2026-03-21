<?php
include_once("db/DBValue.php");

class DBExpression extends DBValue {

    protected string $expr = "";

    public function __construct(string $expr)
    {
        parent::__construct();
        $this->expr = $expr;
    }

    public function value() : string
    {
        return $this->expr;
    }
}