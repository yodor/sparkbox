<?php
include_once("lib/utils/Session.php");

class SessionData
{
    const EMAIL = "email";
    const FULLNAME = "fullname";
    const MENU = "menu";
    const AUTH_TOKEN = "auth_token";
    const LOGIN_TOKEN = "login_token";

    protected $data = array();
    protected $name = "";

    public function __construct(string $name)
    {
        if (Session::Contains($name)) {
            $this->data = Session::Get($name);
        }
        $this->name = $name;
    }

    public function name()
    {
        return $this->name;
    }
    public function clear()
    {
        Session::Clear($this->name);
    }

    public function set(string $key, $val)
    {
        $this->data[$key] = $val;
        Session::Set($this->name, $this->data);
    }

    public function get(string $key)
    {
        if (!isset($this->data[$key])) throw new Exception("SessionData key not found: ".$key);
        return $this->data[$key];
    }
    public function contains(string $key)
    {
        if (isset($this->data[$key])) {
            return true;
        }
        return false;
    }
}