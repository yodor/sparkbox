<?php

/**
 * Abstract class for doing authentication
 * Reimplement to get context specific authenticator
 */
abstract class Authenticator
{

    const CONTEXT_TOKEN = "token";
    const CONTEXT_ID = "id";
    const CONTEXT_DATA = "data";

    protected $contextName = "";
    protected $loginURL = "";

    public function __construct(string $contextName)
    {
        $this->contextName = $contextName;
    }

    public static function HMAC($key, $data, $hash = 'md5', $blocksize = 64)
    {
        if (strlen($key) > $blocksize) {
            $key = pack('H*', $hash($key));
        }
        $key = str_pad($key, $blocksize, chr(0));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        return $hash(($key ^ $opad) . pack('H*', $hash(($key ^ $ipad) . $data)));
    }

    public static function RandomToken(int $length = 32)
    {
        $md5_size = 32;

        if ($length > $md5_size) {
            $length = $md5_size;
        }

        // Generate string with random data (max length $md5_size)
        $string = md5(microtime(true) . "|" . rand());

        // Position Limiting
        $start_point = 32 - $length;

        // Take a random starting point in the randomly
        // Generated String, not going any higher then $start_point
        return substr($string, rand(0, $start_point), $length);
    }


    /**
     * Called from Login or after registration routines are complete to set the logged in state of the context
     * Store authentication data in session and cookie for a named context '$name'
     * @param int $id
     * @param array $data
     * @throws Exception
     */
    public function store(int $id, array $data)
    {
        debug(get_class($this) . " Storing authentication data ... ");

        session_regenerate_id(true);

        debug(get_class($this) . " SessionID regenerated ...");

        $token = Authenticator::RandomToken();

        $context = array();
        $context[Authenticator::CONTEXT_TOKEN] = $token;
        $context[Authenticator::CONTEXT_ID] = $id;
        $context[Authenticator::CONTEXT_DATA] = $data;

        Session::Set($this->contextName, $context);

        setcookie($this->contextName . "_" . Authenticator::CONTEXT_TOKEN, $token, 0, "/");
        setcookie($this->contextName . "_" . Authenticator::CONTEXT_ID, $id, 0, "/");

        //        if (is_array($data)) {
        //            foreach ($data as $key => $val) {
        //                setcookie($this->contextName."_".Authenticator::CONTEXT_DATA."_".$key, $val, 0, "/");
        //            }
        //        }

        debugArray(get_class($this) . " Context data: ", Session::Get($this->contextName));
        debugArray(get_class($this) . " Cookie data: ", $_COOKIE);
        // do not redirect below
    }

    /**
     * Perform checking of the authenticated state stored into the session and cookies
     * @param bool $skip_cookie_check
     * @param array|null $user_data
     * @return bool
     */
    public function validate(bool $skip_cookie_check = false, array $user_data = NULL)
    {

        debug(get_class($this) . " Validate authenticated state: skip_cookie_check: " . $skip_cookie_check);

        if (!Session::Contains($this->contextName)) {
            debug(get_class($this) . " Session array does not contain context name: " . $this->contextName);
            return false;
        }

        $context = Session::Get($this->contextName);

        debugArray(get_class($this) . " context name: " . $this->contextName . " Data: ", $context);

        if (!isset($context[Authenticator::CONTEXT_TOKEN]) || !isset($context[Authenticator::CONTEXT_ID])) return false;

        $session_token = $context[Authenticator::CONTEXT_TOKEN];
        $session_id = $context[Authenticator::CONTEXT_ID];

        if ($skip_cookie_check) {
            return true;
        }

        debug(get_class($this) . " Validating cookie values");


        //session expired
        if (!isset($_COOKIE[$this->contextName . "_" . Authenticator::CONTEXT_TOKEN]) || !isset($_COOKIE[$this->contextName . "_" . Authenticator::CONTEXT_ID])) {
            debug(get_class($this) . " Required cookies were not found");
            return false;
        }

        $cookie_token = $_COOKIE[$this->contextName . "_" . Authenticator::CONTEXT_TOKEN];
        $cookie_id = $_COOKIE[$this->contextName . "_" . Authenticator::CONTEXT_ID];

        if (strcmp($session_token, $cookie_token) == 0 && strcmp($session_id, $cookie_id) == 0) {
            debug(get_class($this) . " Cookie values matched successfully");
            return true;
        }

        debug(get_class($this) . " Cookie values does not match session values");

        return false;
    }

    /**
     * Logout. Clears any stored authentication data for a named context - '$name'
     */
    public function logout()
    {
        Session::Clear($this->contextName);
        foreach ($_COOKIE as $key => $val) {
            if (strpos($key, $this->contextName) === 0) {
                setcookie($key, "", 1, "/", COOKIE_DOMAIN);
            }
        }
    }


    /**
     * Return the context name this class is handling
     * @return string
     */
    public function name()
    {
        return $this->contextName;
    }

    public function getLoginURL()
    {
        return $this->loginURL;
    }

    public function setLoginURL(string $url)
    {
        $this->loginURL = $url;
    }

    /**
     * @param bool $skip_cookie_check
     * @param array|null $user_data
     * @return mixed|null
     */
    public function data(bool $skip_cookie_check = false, array $user_data = NULL)
    {
        $context = NULL;

        if ($this->validate($skip_cookie_check, $user_data)) {
            $context = Session::Get($this->contextName, NULL);
        }

        return $context;
    }

    /**
     * @param $username
     * @param $pass
     * @param $rand
     * @param bool $remember_me
     * @param bool $check_password_only
     * @throws Exception
     */
    abstract public function authenticate(string $username, string $pass, $rand, bool $remember_me = false, bool $check_password_only = false);

    /**
     * @param $userID
     * @throws Exception
     */
    abstract public function updateLastSeen($userID);
}

?>
