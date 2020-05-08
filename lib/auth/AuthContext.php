<?php
include_once("utils/SessionData.php");

class AuthContext
{

    protected $id = -1;
    protected $data = array();

    public function __construct(int $id, SessionData $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    public function getID() : int
    {
        return $this->id;
    }

    public function getData() : SessionData
    {
        return $this->data;
    }

}