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
        session_start();
        Session::$is_started = TRUE;
        //debug("Starting session with ID: " . session_id());
    }

    public static function Destroy()
    {
        session_destroy();
        Session::$is_started = FALSE;
    }

    public static function Close()
    {
        session_write_close();
    }

    public static function Contains(string $key)
    {
        return isset($_SESSION[$key]);
    }

    public static function Get(string $key, $default = NULL)
    {
        if (Session::Contains($key)) {
            return $_SESSION[$key];
        }
        return $default;
    }

    public static function Set(string $key, $val)
    {
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
        setcookie($key, $val, $expire, LOCAL, COOKIE_DOMAIN);
    }

    public static function ClearCookie($key)
    {
        setcookie($key, "", 1, LOCAL, COOKIE_DOMAIN);
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
            Session::Clear(Session::ALERT);
        }
    }

    public static function GetAlert()
    {
        return Session::Get(Session::ALERT, "");
    }
}

?>