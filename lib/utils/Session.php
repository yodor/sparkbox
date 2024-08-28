<?php

/**
 * Class Session
 * Session/Cookie access
 */
class Session
{
    protected static $is_started = FALSE;

    public const ALERT = "alert";

    public static function Start()
    {
        if (!Session::$is_started) {
            session_start();
        }
        Session::$is_started = TRUE;
        //debug("Starting session with ID: " . session_id());
    }

    public static function Destroy()
    {
        if (Session::$is_started) {
            session_destroy();
        }
        Session::$is_started = FALSE;
    }

    public static function Close()
    {
        if (Session::$is_started) {
            session_write_close();
        }
    }


    public static function Contains(string $key)
    {
        Session::Start();
        return isset($_SESSION[$key]);
    }

    public static function Get(string $key, $default = NULL)
    {
        Session::Start();
        if (Session::Contains($key)) {
            return $_SESSION[$key];
        }
        return $default;
    }

    public static function Set(string $key, $val)
    {
        Session::Start();
        $_SESSION[$key] = $val;
    }

    public static function Clear(string $key)
    {
        if (Session::Contains($key)) {
            unset($_SESSION[$key]);
        }
    }

    public static function SetCookie($key, $val, $expire = 0)
    {
        $cookie_path = LOCAL;
        if (!$cookie_path) $cookie_path = "/";

        $_COOKIE[$key] = $val;
        setcookie($key, $val, $expire, $cookie_path, COOKIE_DOMAIN);

    }

    public static function ClearCookie($key)
    {
        $cookie_path = LOCAL;
        if (!$cookie_path) $cookie_path = "/";

        setcookie($key, "", 1, $cookie_path, COOKIE_DOMAIN);
        if (isset($_COOKIE[$key])) {
            unset($_COOKIE[$key]);
        }
    }

    public static function GetCookie($key, $default = FALSE)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
        else {
            return $default;
        }
    }

    public static function HaveCookie($key)
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

    public static function GetAlert()
    {
        return Session::Get(Session::ALERT, "");
    }

    public static function ClearAlert()
    {
        Session::Clear(Session::ALERT);
    }
}

?>
