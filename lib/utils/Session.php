<?php

class Session
{
    protected static bool $Started = false;
    protected static bool $NeedSync = false;

    const string ALERT = "alert";

    private function __construct()
    {

    }

    public static function Start() : void
    {
        if (!Session::$Started) {

            $filename = "";
            $line = "";
            if (headers_sent($filename, $line)) {
                throw new Exception("Headers already sent in $filename line $line");
            }

            // Grouped cookie parameters - this replaces individual ini_set calls for cookies
            session_set_cookie_params([
                "lifetime"   => 0, // Session cookie (expires on browser close)
                "path"       => Spark::Get(Config::LOCAL) ?: "/",
                "domain"     => Spark::Get(Config::COOKIE_DOMAIN),
                "secure"     => true,
                "httponly"   => true,
                "samesite"   => "Strict",
            ]);

            // Global session behavior settings
            ini_set("session.use_strict_mode", 1);
            ini_set("session.gc_maxlifetime", 1440); // 24 minutes server-side TTL

            // Caching headers for dynamic pages
            // "private" allows browser caching but forbids proxy caching (CDN/Varnish)
            //session_cache_limiter("private");
            //session_cache_expire(5); // 60 minutes browser cache freshness

            session_start();

            Session::$Started = true;
            Session::$NeedSync = false;

            Debug::ErrorLog("Starting session ID: " . session_id());
        }
    }

    public static function Destroy() : void
    {
        if (Session::$Started) {

            //Reset the session array in memory
            $_SESSION = array();

            //Delete the session cookie
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                1,
                $params['path'],
                $params['domain'],
                $params['secure'],
                isset($params['httponly'])
            );

            //Destroy the session
            session_write_close();
            session_destroy();

            Session::$Started = false;
            Session::$NeedSync = false;
        }

    }

    /**
     * Releases the write lock to allow other concurrent requests (performance optimization).
     */
    public static function Close() : void
    {
        if (Session::$Started) {
            Debug::ErrorLog("Releasing write lock: " . session_id());
            session_write_close();
            Session::$NeedSync = false;
        }
    }

    public static function NeedSync() : bool
    {
        return Session::$NeedSync;
    }

    public static function Contains(string $key) : bool
    {
        if (!Session::$Started) {
            Session::Start();
        }
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
        if (!Session::$Started) {
            Session::Start();
        }
        $_SESSION[$key] = $val;
        Session::$NeedSync = true;
    }

    public static function Remove(string $key) : void
    {
        if (Session::Contains($key)) {
            unset($_SESSION[$key]);
            Session::$NeedSync = true;
        }
    }

    public static function SetCookie(string $key, string $val, $expire = 0) : void
    {
        $cookiePath = Spark::Get(Config::LOCAL) ?: "/";

        $_COOKIE[$key] = $val;

        setcookie(
            $key,
            $val,
            $expire,
            $cookiePath,
            Spark::Get(Config::COOKIE_DOMAIN),
            true,
            true
        );
    }

    public static function ClearCookie(string $key) : void
    {
        $cookiePath = Spark::Get(Config::LOCAL) ?: "/";

        setcookie(
            $key,
            "",
            1,
            $cookiePath,
            Spark::Get(Config::COOKIE_DOMAIN),
            true,
            true
        );

        if (isset($_COOKIE[$key])) {
            unset($_COOKIE[$key]);
        }
    }

    public static function GetCookie(string $key, string $default = "") : string
    {
        return $_COOKIE[$key] ?? $default;
    }

    public static function HaveCookie(string $key) : bool
    {
        return isset($_COOKIE[$key]);
    }

    public static function SetAlert(string $msg) : void
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