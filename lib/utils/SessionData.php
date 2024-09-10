<?php
include_once("utils/Session.php");

/**
 * Store array in session
 */
class SessionData
{
    const string EMAIL = "email";
    const string FULLNAME = "fullname";
    const string MENU = "menu";
    const string AUTH_TOKEN = "auth_token";
    const string LOGIN_TOKEN = "login_token";
    const string UPLOAD_CONTROL = "upload_control";

    protected array $data = array();
    protected string $name = "";

    public static function Prefix(string $name, string $prefix) : string
    {
        return $prefix."-".$name;
    }

    public function __construct(string $name)
    {
        $this->name = $name;


        if (Session::Contains($name)) {
            $this->data = Session::Get($name);
            debug("SessionData [$this->name] found in session. Data count: ".count($this->data));
        }
        else {
            debug("SessionData [$this->name] not found in session");
        }
    }


    protected function storeData() : void
    {
        debug("Storing SessionData [$this->name] to session. Data count: ".count($this->data));
        Session::Set($this->name, $this->data);
        //debug("Data: ",$this->data);
    }

    public function clear() : void
    {
        debug("Removing SessionData [$this->name] from session");
        Session::Clear($this->name);
    }

    public function name() : string
    {
        return $this->name;
    }

    public function set(string $key, mixed $val) : void
    {
        $this->data[$key] = $val;
        $this->storeData();
    }

    public function get(string $key) : mixed
    {
        if (!isset($this->data[$key])) throw new Exception("SessionData key not found: " . $key);
        return $this->data[$key];
    }

    public function contains(string $key) : bool
    {
        if (isset($this->data[$key])) {
            return TRUE;
        }
        return FALSE;
    }

    public function remove(string $key) : void
    {
        if ($this->contains($key)) {
            unset($this->data[$key]);
            $this->storeData();
        }
    }

    public function count() : int
    {
        return count($this->data);
    }

    public function keys() : array
    {
        return array_keys($this->data);
    }
}