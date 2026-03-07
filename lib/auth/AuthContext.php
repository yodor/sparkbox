<?php
include_once("utils/SessionData.php");

class AuthContext
{
    const string EMAIL = "email";
    const string FULLNAME = "fullname";

    const string AUTH_TOKEN = "auth_token";
    const string LOGIN_TOKEN = "login_token";

    protected int $id = -1;
    protected SessionData $data;

    public function __construct(int $id, SessionData $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getData(): SessionData
    {
        return $this->data;
    }

}