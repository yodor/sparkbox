<?php

class OrderField extends OrderColumn
{
    protected array $fields = [];

    public function __construct(string $enumName, string ...$fields)
    {
        parent::__construct($enumName);
        $this->fields = $fields;
    }

    public function getSQL() : string
    {
        $quoted = array_map(function ($value) : string {
            return "'" . $value . "'";
        }, $this->fields);
        return "FIELD($this->name, " . implode("'", $quoted). ")";
    }
}