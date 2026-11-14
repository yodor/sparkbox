<?php

class PasswordHash
{
    /**
     * password_hash type
     * @var string
     */
    protected string $type = "2y";
    /**
     * Options
     * @var array|int[]
     */
    protected array $options = array();
    /**
     * password_hash of plaintext
     * @var string
     */
    protected ?string $value = null;
    /**
     * Plaintext password
     * @var string
     */
    protected string $password = "";

    /**
     * Do password_hash on plaintext $password
     *
     * @param string $password
     * @throws Exception
     */
    public function __construct(string $password)
    {
        if (!$password) throw new Exception(tr("Password cannot be empty"));
        $this->password = $password;

        $this->type = Spark::Get(Config::PASSWORD_TYPE);
        if (!in_array($this->type, password_algos())) {
            throw new Exception(tr("Unsupported hash type: ".$this->type));
        }

        $options = Spark::GetObject(Config::PASSWORD_OPTIONS);
        if (!is_array($options)) {
            throw new Exception(tr("Options must be an array"));
        }
        $this->options = $options;

        $this->value = null;
        //
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Compute expensive!
     * @return string
     */
    public function getValue(): string
    {
        if (is_null($this->value)) $this->value = password_hash($this->password, $this->type, $this->options);
        return $this->value;
    }

    /**
     * Compute expensive!
     * Do password_verify using $this->password and $storedHash
     * @param string $storedHash
     * @return bool
     */
    public function verify(string $storedHash): bool
    {
       return password_verify($this->password, $storedHash);
    }

    public function needRehash(string $storedHash): bool
    {
        return password_needs_rehash($storedHash, $this->getType(), $this->getOptions());
    }

    /**
     * Hmac verify the challenge token with client_hmac
     * @param string $token
     * @param string $client_hmac
     * @return bool
     */
    public function hmacVerify(string $token, string $client_hmac): bool
    {
        $server_hmac = hash_hmac('sha256', $token, hash("sha256", $this->password, false),false);
        return hash_equals($server_hmac, $client_hmac);
    }

    /**
     * Verify legacy MD5 hash, compute md5 on $this->password and return hash_equals with $storedHash
     * @param string $storedHash
     * @return bool
     */
    public function digestVerify(string $storedHash): bool
    {
        $computed = hash("md5", $this->password, false);
        return hash_equals($computed, $storedHash);
    }
}