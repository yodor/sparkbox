<?php

class LinkedData
{
    protected array $data = array();

    protected array $type = array();

    public function __construct(string ...$type)
    {
        $this->setType(...$type);
    }

    public function setType(string ...$type) : void
    {
        $this->type = $type;
    }

    public function getType() : array
    {
        return $this->type;
    }

    public function set(string $key, string|array $value) : void
    {
        $this->data[$key] = $value;
    }

    public function setArray(string $key, ...$value) : void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key) : string|array
    {
        return $this->data[$key];
    }

    public function isSet(string $key) : bool
    {
        return isset($this->data[$key]);
    }

    public function toArray() : array
    {
        return array("@type"=>$this->type) + $this->data;
    }
}
?>