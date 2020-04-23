<?php

class AuthData
{
    protected $data = array();

    const DATA_EMAIL = "email";
    const DATA_FULLNAME = "fullname";

    public function __construct()
    {
    }

    public function set(string $key, $val)
    {
        return $this->data[$key] = $val;
    }

    public function get(string $key)
    {
        if (!is_set($this->data[$key]))return null;

        return $this->data[$key];
    }
}