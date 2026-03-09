<?php
include_once("utils/Session.php");
include_once("objects/ISparkSerialize.php");

/**
 * Store array in session
 */
class SessionData
{
    //data key
    const string MENU = "menu";

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

    public function __destruct()
    {
        Debug::ErrorLog("SessionData [$this->name] destructor");
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
        $result = $val;
        if ($val instanceof ISparkSerializable) {
            $result = $val->wrap();
        }
        $this->data[$key] = $result;
    }

    public function get(string $key) : mixed
    {
        if (!isset($this->data[$key])) throw new Exception("SessionData key not found: " . $key);
        $value = $this->data[$key];
        if ($value instanceof ISparkUnserializable) {
            return $value->unwrap();
        }
        return $value;
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