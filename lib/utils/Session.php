<?php

class Session
{
    protected static bool $is_started = false;

    const string ALERT = "alert";

    private function __construct()
    {

    }

    public static function Start() : void
    {
        if (!Session::$is_started) {
            session_start();
            Session::$is_started = TRUE;
            debug("Starting session ID: " . session_id());
        }
    }

    public static function Destroy() : void
    {
        if (Session::$is_started) {
            //Unset individual session variables
            foreach ($_SESSION as $key => $value) {
                unset($_SESSION[$key]);
            }
            //Reset the session array
            $_SESSION = array();

            //Destroy the session
            session_destroy();

            //Delete the session cookie
            $params = session_get_cookie_params();
            setcookie(session_name(), '', 1, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));

            //Verify session file deletion (if file-based storage)
            //unlink(session_save_path() . '/' . session_id());

            Session::$is_started = FALSE;
        }

    }

    public static function Close() : void
    {
        if (Session::$is_started) {
            session_write_close();
        }
    }


    public static function Contains(string $key) : bool
    {
        Session::Start();
        return isset($_SESSION[$key]);
    }

    public static function Get(string $key, mixed $default = NULL) : mixed
    {
        if (Session::Contains($key)) {
            return $_SESSION[$key];
        }
        return $default;
    }

    public static function &GetRef(string $key) : mixed
    {
        if (Session::Contains($key)) {
            return $_SESSION[$key];
        }
        throw new Exception("Key not found in session");
    }

    public static function Set(string $key, mixed $val) : void
    {
        Session::Start();
        $_SESSION[$key] = $val;
    }

    public static function Remove(string $key) : void
    {
        if (Session::Contains($key)) {
            unset($_SESSION[$key]);
        }
    }

    public static function SetCookie(string $key, $val, $expire = 0) : void
    {
        $cookie_path = LOCAL;
        if (!$cookie_path) $cookie_path = "/";

        $_COOKIE[$key] = $val;
        setcookie($key, $val, $expire, $cookie_path, COOKIE_DOMAIN);

    }

    public static function ClearCookie(string $key) : void
    {
        $cookie_path = LOCAL;
        if (!$cookie_path) $cookie_path = "/";

        setcookie($key, "", 1, $cookie_path, COOKIE_DOMAIN);
        if (isset($_COOKIE[$key])) {
            unset($_COOKIE[$key]);
        }
    }

    public static function GetCookie(string $key, $default = FALSE)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
        else {
            return $default;
        }
    }

    public static function HaveCookie(string $key)
    {
        return isset($_COOKIE[$key]);
    }

    public static function SetAlert(string $msg)
    {
        if (strlen($msg) > 0) {
            Session::Set(Session::ALERT, $msg);
        }
        else {
            Session::ClearAlert();
        }
    }

    public static function GetAlert() : string
    {
        return Session::Get(Session::ALERT, "");
    }

    public static function ClearAlert() : void
    {
        Session::Remove(Session::ALERT);
    }
}

?>
