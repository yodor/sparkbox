<?php

/**
 * Class Session
 * Session/Cookie access
 */
class Session
{
    protected static bool $is_started = false;

    public const string ALERT = "alert";

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
            foreach ($_SESSION as $key => $value) {
                unset($_SESSION[$key]);
            }
            session_write_close();
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

    public static function Set(string $key, mixed $val) : void
    {
        Session::Start();
        $_SESSION[$key] = $val;
    }

    public static function Clear(string $key) : void
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
        Session::Clear(Session::ALERT);
    }
}

?>
