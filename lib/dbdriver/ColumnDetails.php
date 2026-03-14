<?php

class ColumnDetails
{
    protected string $name = "";
    protected string $type = "";
    protected bool $nullable = false;
    protected string $keyType = "";
    protected string $default = "";
    protected string $extra = "";

    public function __construct(array $data)
    {
        //Field   //Type             //Null //Key   //Default   //Extra
        //userID  //int(11) unsigned //NO   //PRI   //NULL      //auto_increment
        $this->name = $data["Field"];
        $this->type = $data["Type"];
        $this->nullable = (strcmp($data["Null"], "YES") === 0);
        $this->keyType = $data["Key"];
        $this->default = $data["Default"];
        $this->extra = $data["Extra"];
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function keyType(): string
    {
        return $this->keyType;
    }

    public function defaultValue(): string
    {
        return $this->default;
    }

    public function extra(): string
    {
        return $this->extra;
    }

}