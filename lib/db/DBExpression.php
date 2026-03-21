<?php
include_once("db/IDBValue.php");

class DBExpression implements IDBValue {

    protected ?string $value = null;

    public function __construct(string $expression)
    {
        $this->value = $expression;
    }

    public function value() : string|float|int|bool|null
    {
        return $this->value;
    }

    public function bindingKey(): string|null
    {
        return null;
    }

}