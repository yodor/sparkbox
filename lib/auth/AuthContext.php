<?php
include_once("lib/utils/SessionData.php");

class AuthContext
{

    protected $id = -1;
    protected $data = array();

    public function __construct(int $id, SessionData $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    public function getID()
    {
        return $this->id;
    }

    public function getData()
    {
        return $this->data;
    }

}