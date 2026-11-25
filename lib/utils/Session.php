<?php
include_once("objects/events/SessionEvent.php");

class Session
{
    protected static bool $Started = false;

    const string ALERT = "alert";

    /**
     * The session cookie of the PHP default (before the SparkBox names) - a session found under it is carried over
     * once to the SparkBox name (Start)
     */
    const string LEGACY_SESSION = "PHPSESSID";

    /**
     * The name of the session cookie: the cookie prefix + 'session' (Config::COOKIE_PREFIX)
     */
    public static function Name() : string
    {
        return Spark::Get(Config::COOKIE_PREFIX, "sb_") . "session";
    }

    /**
     * The visitor has a session cookie (the SparkBox one, or the legacy one to carry over)
     */
    protected static function HaveSessionCookie() : bool
    {
        return isset($_COOKIE[Session::Name()]) || isset($_COOKIE[Session::LEGACY_SESSION]);
    }

    /**
     * The name of a SparkBox cookie: the cookie prefix + '$key'
     */
    protected static function CookieName(string $key) : string
    {
        return Spark::Get(Config::COOKIE_PREFIX, "sb_") . $key;
    }

    private function __construct()
    {

    }

    public static function Start() : void
    {
        if (!Session::$Started) {

            $filename = "";
            $line = "";
            if (headers_sent($filename, $line)) {
                throw new Exception("Headers have already been sent: " . $filename. " : ". $line);
            }

            session_name(Session::Name());
            //a session of the legacy cookie (PHPSESSID - before the SparkBox names): carried over once. The strict
            //mode below starts a new session for an id this site does not know (another site's cookie). Two or more
            //PHPSESSID cookies (a parent domain site's and this one's) - which is ours is unknown: none is taken.
            $legacy = (!isset($_COOKIE[Session::Name()]) && isset($_COOKIE[Session::LEGACY_SESSION]))
                ? (string)$_COOKIE[Session::LEGACY_SESSION] : "";
            $sent = preg_match_all("/(?:^|;)\\s*" . Session::LEGACY_SESSION . "=/", (string)($_SERVER["HTTP_COOKIE"] ?? ""));
            if ($legacy !== "" && $sent === 1 && preg_match("/^[a-zA-Z0-9,-]{22,256}$/", $legacy)) session_id($legacy);

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

            // 1. Turn off header generation
            session_cache_limiter("");
            //session_cache_expire(5); // 60 minutes browser cache freshness

            SparkEventManager::emit(new SessionEvent(SessionEvent::STARTING));
            session_start();

            if ($legacy !== "") {
                //session_start sent the cookie under the SparkBox name (the request had none); the legacy cookie
                //removed - the host-only one and the one of the old '.host' domain
                $params = session_get_cookie_params();
                setcookie(Session::LEGACY_SESSION, "", 1, $params["path"], "", true, true);
                $host = (string)preg_replace("/:\\d+$/", "", (string)($_SERVER["HTTP_HOST"] ?? ""));
                if ($host !== "") setcookie(Session::LEGACY_SESSION, "", 1, $params["path"], "." . $host, true, true);
                unset($_COOKIE[Session::LEGACY_SESSION]);
            }

            // 2. Rule the headers
            header("Cache-Control: private, must-revalidate, max-age=0");

            Session::$Started = true;
            //the session exists from now on - reads later in this request (after Close) see it
            $_COOKIE[session_name()] = session_id();

            Debug::ErrorLog("Starting session ID: " . session_id());
            SparkEventManager::emit(new SessionEvent(SessionEvent::STARTED));
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
            Session::Close();
            SparkEventManager::emit(new SessionEvent(SessionEvent::DESTROYING));
            session_destroy();
            SparkEventManager::emit(new SessionEvent(SessionEvent::DESTROYED));

        }

    }

    /**
     * Releases the write lock to allow other concurrent requests (performance optimization).
     */
    public static function Close() : void
    {
        if (Session::$Started) {
            SparkEventManager::emit(new SessionEvent(SessionEvent::CLOSING));
            Debug::ErrorLog("Releasing write lock: " . session_id());
            session_write_close();
            Session::$Started = false;
            SparkEventManager::emit(new SessionEvent(SessionEvent::CLOSED));
        }
        else {
            Debug::ErrorLog("Session already closed");
        }
    }

    public static function IsStarted() : bool
    {
        return Session::$Started;
    }

    public static function Contains(string $key) : bool
    {
        if (!Session::$Started) {
            //no session cookie - nothing is stored for this visitor: a read does not start a session (no session
            //cookie for the visitors who only read - the public pages stay cacheable); a write (Set) starts it
            if (!Session::HaveSessionCookie()) return false;
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

    public static function Set(string $key, mixed $val) : void
    {
        if (!Session::$Started) {
            Session::Start();
        }
        $_SESSION[$key] = $val;
    }

    /**
     * Set '$key' only for a visitor who has a session already - a value that matters only next to other session
     * data (the page to go back to from the cart): it does not start a session for a visitor who only reads
     * @param string $key
     * @param mixed $val
     * @return void
     */
    public static function SetIfActive(string $key, mixed $val) : void
    {
        if (!Session::$Started && !Session::HaveSessionCookie()) return;
        Session::Set($key, $val);
    }

    public static function Remove(string $key) : void
    {
        if (Session::Contains($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * A SparkBox cookie '$key' (the name gets Config::COOKIE_PREFIX) - secure, http only, the site's own
     * (Config::COOKIE_DOMAIN)
     */
    public static function SetCookie(string $key, string $val, $expire = 0) : void
    {
        $cookiePath = Spark::Get(Config::LOCAL) ?: "/";
        $key = Session::CookieName($key);

        $_COOKIE[$key] = $val;

        //output started - the cookie can not be sent any more (this request still sees the value)
        if (headers_sent()) {
            Debug::ErrorLog("Cookie '$key' not sent - headers already sent");
            return;
        }

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
        $key = Session::CookieName($key);

        if (headers_sent()) {
            Debug::ErrorLog("Cookie '$key' not cleared - headers already sent");
        }
        else setcookie(
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

    /**
     * The keys (without Config::COOKIE_PREFIX) of the SparkBox cookies of this request starting with '$start'
     * @return string[]
     */
    public static function CookieKeys(string $start = "") : array
    {
        $prefix = Session::CookieName("");
        $keys = array();
        foreach (array_keys($_COOKIE) as $name) {
            $name = (string)$name;
            if (str_starts_with($name, $prefix . $start)) $keys[] = substr($name, strlen($prefix));
        }
        return $keys;
    }

    public static function GetCookie(string $key, string $default = "") : string
    {
        return $_COOKIE[Session::CookieName($key)] ?? $default;
    }

    public static function HaveCookie(string $key) : bool
    {
        return isset($_COOKIE[Session::CookieName($key)]);
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