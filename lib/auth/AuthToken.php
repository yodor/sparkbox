<?php

class AuthToken
{
    const HASH = "hash";
    const ID = "id";

    const TOKEN_LENGTH = 32;

    /**
     * @var int
     */
    protected int $id = -1;

    /**
     * @var string
     */
    protected string $hash = "";

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->hash = Authenticator::RandomToken(AuthToken::TOKEN_LENGTH);
    }

    public function getID()
    {
        return $this->id;
    }

    public function getHash() : string
    {
        return $this->hash;
    }

    public function storeCookies(string $contextName)
    {
        //hash_hmac ( string $algo , string $data , string $key [, bool $raw_output = FALSE ] ) : string
        Session::SetCookie($contextName . "_" . AuthToken::HASH, $this->hash);
        Session::SetCookie($contextName . "_" . AuthToken::ID, $this->id);
    }

    public function validateCookies(string $contextName)
    {
        if ($this->id < 1 || strlen($this->hash) != AuthToken::TOKEN_LENGTH) {
            debug("Token data invalid");
            return FALSE;
        }

        if (!Session::HaveCookie($contextName . "_" . AuthToken::HASH) || !Session::HaveCookie($contextName . "_" . AuthToken::ID)) {
            debug("Required cookies were not found");
            return FALSE;
        }

        $cookie_hash = Session::GetCookie($contextName . "_" . AuthToken::HASH);
        $cookie_id = (int)Session::GetCookie($contextName . "_" . AuthToken::ID);

        if (strcmp($cookie_hash, $this->hash) == 0 && $cookie_id == $this->id) {
            debug("Cookie values matched successfully");
            return TRUE;
        }

        debug("Cookie values mismatch");
        return FALSE;
    }

    public function __serialize() : array
    {
        return get_object_vars($this);
    }
}

?>
