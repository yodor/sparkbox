<?php

/**
 * Class Session
 * Session/Cookie access
 */
class Session
{
    protected static $is_started = false;

    public static function Start()
    {
        session_start();
        Session::$is_started = true;
        debug("Starting session with ID: " . session_id());
    }

    public static function Destroy()
    {
        session_destroy();
        Session::$is_started = false;
    }

    public static function Contains(string $key)
    {
        return isset($_SESSION[$key]);
    }

    public static function Get(string $key, $default = NULL)
    {
        if (isset($_SESSION[$key])) {
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
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function SetCookie($key, $val, $expire = false)
    {
        if (!$expire) {
            $expire = time() + 60 * 60 * 24 * 365;
        }

        setcookie($key, $val, $expire, "/", COOKIE_DOMAIN);
    }

    public static function GetCookie($key, $default = false)
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
}

?>