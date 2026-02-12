<?php

class LinkedData
{
    protected array $data = array();

    protected array $type = array();

    public function __construct(string ...$type)
    {
        $this->setType(...$type);
    }

    public function setID(string $id) : void
    {
        $this->set("@id",$id);
    }
    public function getID() : string
    {
        return $this->get("@id");
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
        $result = $this->data;

        $count = count($this->type);

        if ($count == 1) {
            $result = array("@type"=>$this->type[0]) + $result;
        }
        else if ($count > 1) {
            $result = array("@type"=>$this->type) + $result;
        }

        return $result;
    }
}