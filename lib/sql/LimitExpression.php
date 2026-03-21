<?php

class LimitExpression {

    protected ?int $count = null;
    protected ?int $offset = null;

    public function __construct()
    {
        $this->count = null;
        $this->offset = null;
    }

    public function set(int $count, ?int $offset = null) : void
    {
        $this->count = $count;
        $this->offset = $offset;
    }

    public function empty() : void
    {
        $this->count = null;
        $this->offset = null;
    }

    public function isEmpty() : bool
    {
        return !is_null($this->count);
    }

    public function count() : ?int
    {
        return $this->count;
    }

    public function offset() : ?int
    {
        return $this->offset;
    }

    public function getSQL() : string
    {
        if (is_null($this->count)) return "";

        $result = " LIMIT $this->count";

        if (!is_null($this->offset)) {
            $result .= " OFFSET $this->offset";
        }

        return $result;
    }
}