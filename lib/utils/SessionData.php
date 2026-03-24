<?php
include_once("utils/Session.php");
include_once("objects/ISparkSeal.php");
include_once("objects/ISparkUnseal.php");
include_once("objects/SparkSealed.php");
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

    protected bool $need_sync = false;

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
            $this->need_sync = true;
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
        if ($this->need_sync) {
            Session::Close();
        }
    }

    public function removeAll() : void
    {
        $keys = array_keys($this->data);
        foreach ($keys as $idx=>$key) {
            unset($this->data[$key]);
            $this->need_sync = true;
        }
    }

    public function destroy() : void
    {
        Debug::ErrorLog("Removing SessionData [$this->name] from session");
        $this->removeAll();
        Session::Remove($this->name);
        Session::Close();
        $this->need_sync = false;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function set(string $key, ISparkSeal|ISerializable|array|string|float|int|bool $val) : void
    {
        $result = $val;
        if ($val instanceof ISparkSeal) {
            $result = $val->wrap();
        }
        $this->data[$key] = $result;

        $this->need_sync = true;
    }

    public function get(string $key) : mixed
    {
        if (!isset($this->data[$key])) throw new Exception("SessionData key not found: " . $key);
        $value = $this->data[$key];
        if ($value instanceof ISparkUnseal) {
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
            $this->need_sync = true;
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