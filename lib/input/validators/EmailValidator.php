<?php
include_once("input/validators/IInputValidator.php");

class EmailValidator implements IInputValidator
{
    protected bool $domain_check_enabled = TRUE;

    public function __construct(bool $domain_check_enabled = TRUE)
    {
        $this->domain_check_enabled = $domain_check_enabled;
    }

    public function validate(DataInput $input) : void
    {

        $value = trim($input->getValue());

        if (strlen($value) == 0) throw new Exception("Input value");

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === FALSE) {
            throw new Exception("Incorrect email syntax");
        }

        if ($this->domain_check_enabled) {

            list($username, $domain) = explode('@', $value);

            if (!$this->checkMX($domain)) throw new Exception("Unrecognized email domain");

        }

    }

    //checkdnsrr and getmxrr are really very slow on the current server use this
    protected function checkMX($domain) : bool
    {

        return checkdnsrr($domain . ".", "MX");

    }

}