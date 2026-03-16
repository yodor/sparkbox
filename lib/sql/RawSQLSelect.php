<?php

class RawSQLSelect extends SQLSelect
{
    protected string $queryString = "";

    public function __construct(string $queryString)
    {
        parent::__construct();
        $this->queryString = $queryString;
    }

    public function getSQL() : string
    {
        return $this->queryString;
    }

}