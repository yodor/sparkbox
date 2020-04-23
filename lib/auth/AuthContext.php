<?php


class AuthContext
{
    public const TOKEN = "token";
    public const ID = "id";

    /**
     * @var int
     */
    protected $id = -1;

    /**
     * @var string
     */
    protected $token = "";

    /**
     * @var AuthData|null
     */
    protected $data = null;

    public function __construct(int $id, string $token, AuthData $data)
    {
        $this->id = $id;
        $this->token = $token;
        $this->data = $data;
    }

    public function getID()
    {
        return $this->id;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getData()
    {
        return $this->data;
    }

    public function store(string $contextName)
    {
        //hash_hmac ( string $algo , string $data , string $key [, bool $raw_output = FALSE ] ) : string
        setcookie($contextName . "_" . AuthContext::TOKEN, $this->token, 0, "/");
        setcookie($contextName . "_" . AuthContext::ID, $this->id, 0, "/");
        Session::Set($contextName, serialize($this));
    }

    public function validate(string $contextName)
    {
        if ($this->id<1 || strlen($this->token) != Authenticator::TOKEN_LENGTH) {
            debug(get_class($this) . " AuthContext data missmatch");
            return false;
        }

        if (!isset($_COOKIE[$contextName . "_" . AuthContext::TOKEN]) || !isset($_COOKIE[$contextName . "_" . AuthContext::ID])) {
            debug(get_class($this) . " Required cookies were not found");
            return false;
        }

        $cookie_token = $_COOKIE[$contextName . "_" . AuthContext::TOKEN];
        $cookie_id = (int)$_COOKIE[$contextName . "_" . AuthContext::ID];

        if (strcmp($cookie_token, $this->token) == 0 && $cookie_id == $this->id) {
            debug(get_class($this) . " Cookie values matched successfully");
            return true;
        }

        debug(get_class($this) . " Cookie values does not match");
        return false;
    }


}