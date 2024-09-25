<?php

class RawResult
{
    protected array $result = array();

    public function __construct(array $result)
    {
        $this->result = $result;
    }

    public function get(string $key) : ?string
    {
        return $this->result[$key];
    }

    public function isSet(string $key) : bool
    {
        return isset($this->result[$key]);
    }

    public function toArray() : array
    {
        return $this->result;
    }

    public function &arrayRef() : array
    {
        return $this->result;
    }

    public function keys() : array
    {
        return array_keys($this->result);
    }

    public function iterator() : ArrayIterator
    {
        return new ArrayIterator($this->result);
    }
}
?>
