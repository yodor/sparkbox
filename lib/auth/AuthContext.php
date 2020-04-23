<?php
include_once("lib/auth/AuthData.php");

class AuthToken
{
    /**
     * @var int
     */
    protected $id = -1;

    /**
     * @var string
     */
    protected $hash = "";

    public function __construct(int $id, string $hash)
    {
        $this->id = $id;
        $this->hash = $hash;
    }

    public function getID()
    {
        return $this->id;
    }

    public function getHash()
    {
        return $this->hash;
    }

}

class AuthContext implements Serializable
{
    public const HASH = "hash";
    public const ID = "id";

    const TOKEN_LENGTH = 32;

    const TOKEN = "token";
    const DATA = "data";

    protected $token = NULL;
    /**
     * @var AuthData|null
     */
    protected $data = NULL;

    public function __construct(int $id, AuthData $data)
    {
        $this->token = new AuthToken($id, Authenticator::RandomToken(AuthContext::TOKEN_LENGTH));
        $this->data = $data;
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
        setcookie($contextName . "_" . AuthContext::HASH, $this->token->getHash(), 0, "/");
        setcookie($contextName . "_" . AuthContext::ID, $this->token->getID(), 0, "/");

        if (strcmp($this->data->name(),$contextName)!=0)throw new Exception("AuthData name missmatch");

        Session::Set($contextName, serialize($this->token));
        $this->data->store();
    }

    public function validate(string $contextName)
    {
        if ($this->id<1 || strlen($this->token) != Authenticator::TOKEN_LENGTH) {
            debug(get_class($this) . " AuthContext data missmatch");
            return false;
        }

        if (!isset($_COOKIE[$contextName . "_" . AuthContext::HASH]) || !isset($_COOKIE[$contextName . "_" . AuthContext::ID])) {
            debug(get_class($this) . " Required cookies were not found");
            return false;
        }

        $cookie_hash = $_COOKIE[$contextName . "_" . AuthContext::HASH];
        $cookie_id = (int)$_COOKIE[$contextName . "_" . AuthContext::ID];

        if (strcmp($cookie_hash, $this->token->getHash()) == 0 && $cookie_id == $this->token->getID()) {
            debug(get_class($this) . " Cookie values matched successfully");

            if ($this->data == NULL) {
                $this->data = new AuthData($contextName);
                $this->data->load();
            }

            return true;
        }

        debug(get_class($this) . " Cookie values does not match");
        return false;
    }


    public function serialize()
    {
        return serialize($this->token);
    }

    public function unserialize($serialized)
    {
        $token = unserialize($serialized);
        if ($token instanceof AuthToken) {
            $this->token = $token;
        }
    }
}