<?php

class URLParameter
{
    protected $name;
    protected $value;

    public function __construct(string $name, string $value = "")
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function text(bool $quoteValue = FALSE)
    {
        $ret = $this->name;
        if ($this->value) {
            $ret .= "=";
            if ($quoteValue) $ret .= "'";
            $ret .= $this->value;
            if ($quoteValue) $ret .= "'";
        }
        return $ret;
    }

}