<?php

class AuthToken
{
    const HASH = "hash";
    const ID = "id";

    const TOKEN_LENGTH = 32;

    /**
     * @var int
     */
    protected $id = -1;

    /**
     * @var string
     */
    protected $hash = "";

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->hash = Authenticator::RandomToken(AuthToken::TOKEN_LENGTH);
    }

    public function getID()
    {
        return $this->id;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function storeCookies(string $contextName)
    {
        //hash_hmac ( string $algo , string $data , string $key [, bool $raw_output = FALSE ] ) : string
        setcookie($contextName . "_" . AuthToken::HASH, $this->hash, 0, "/");
        setcookie($contextName . "_" . AuthToken::ID, $this->id, 0, "/");

    }

    public function validateCookies(string $contextName)
    {
        if ($this->id < 1 || strlen($this->hash) != AuthToken::TOKEN_LENGTH) {
            debug(" Token data invalid");
            return false;
        }

        if (!isset($_COOKIE[$contextName . "_" . AuthToken::HASH]) || !isset($_COOKIE[$contextName . "_" . AuthToken::ID])) {
            debug(" Required cookies were not found");
            return false;
        }

        $cookie_hash = $_COOKIE[$contextName . "_" . AuthToken::HASH];
        $cookie_id = (int)$_COOKIE[$contextName . "_" . AuthToken::ID];

        if (strcmp($cookie_hash, $this->hash) == 0 && $cookie_id == (int)$this->id) {
            debug(" Cookie values matched successfully");
            return true;
        }

        debug(get_class($this) . " Cookie values does not match");
        return false;
    }
}
?>