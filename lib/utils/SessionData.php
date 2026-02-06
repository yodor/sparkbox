<?php
include_once("utils/Session.php");

/**
 * Store array in session
 */
class SessionData
{
    //data key
    const string EMAIL = "email";
    const string FULLNAME = "fullname";
    const string MENU = "menu";
    const string AUTH_TOKEN = "auth_token";
    const string LOGIN_TOKEN = "login_token";

    //session name
    const string UPLOAD_CONTROL = "upload_control";

    //reference to S_SESSION[$name] data
    protected array $data;

    protected string $name = "";

    public static function Prefix(string $name, string $prefix) : string
    {
        return $prefix."-".$name;
    }

    public function __construct(string $name)
    {
        $this->name = $name;

        if (!Session::Contains($name)) {
            Debug::ErrorLog("SessionData [$this->name] initializing empty data.");
            Session::Set($name, array());
        }

        if (Session::Contains($name)) {
            if (!is_array(Session::GetRef($name))) throw new Exception("Incorrect SessionData");
            $this->data = &Session::GetRef($name);
            Debug::ErrorLog("SessionData [$this->name] loaded - data count: ".count($this->data));
        }

    }

    public function removeAll() : void
    {
        $keys = array_keys($this->data);
        foreach ($keys as $idx=>$key) {
            unset($this->data[$key]);
        }
    }

    public function destroy() : void
    {
        Debug::ErrorLog("Removing SessionData [$this->name] from session");
        $this->removeAll();
        Session::Remove($this->name);
    }

    public function name() : string
    {
        return $this->name;
    }

    public function set(string $key, mixed $val) : void
    {
        $this->data[$key] = $val;
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