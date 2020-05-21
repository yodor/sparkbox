<?php

/**
 * Ensure the parameter '$name' exists in the current request data
 * Class RequestParameterCondition
 */
class RequestParameterCondition
{
    protected $parameter;
    protected $redirectURL;
    protected $value;

    public function __construct(string $name, string $redirectURL)
    {
        $this->parameter = $name;
        $this->redirectURL = $redirectURL;

        try {
            $this->process();
        }
        catch (Exception $ex) {

            if ($this->redirectURL) {
                Session::SetAlert($ex->getMessage());
                header("Location: $redirectURL");
                exit;
            }
            else {
                throw $ex;
            }
        }

    }

    public function getValue()
    {
        return $this->value;
    }

    protected function process()
    {
        if (!isset($_GET[$this->parameter])) throw new Exception("Key: {$this->parameter} not received in GET");

        $this->value = $_GET[$this->parameter];
    }
}