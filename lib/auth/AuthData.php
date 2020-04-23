<?php

class AuthData
{
    protected $data = array();

    const DATA_EMAIL = "email";
    const DATA_FULLNAME = "fullname";

    protected $contextName = "";

    public function __construct(string $contextName)
    {
        if (strlen($this->contextName)<1) throw new Exception("Empty context name");
        $this->contextName = $contextName;
    }

    public function set(string $key, $val)
    {
        $this->data[$key] = $val;
    }

    public function get(string $key)
    {
        if (!is_set($this->data[$key]))return null;

        return $this->data[$key];
    }

    public function store()
    {
        Session::set($this->contextName.":data", serialize($this->data));
    }

    public function load()
    {
        if (Session::Contains($this->contextName.":data")) {
            $this->data = unserialize(Session::Get($this->contextName . ":data"));
        }
    }

    public function name()
    {
        return $this->contextName;
    }

}