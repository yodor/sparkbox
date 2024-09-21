<?php
include_once("utils/IGETConsumer.php");
/**
 * Ensure the parameter '$name' exists in the current request data
 * Class RequestParameterCondition
 */
class RequestParameterCondition implements IGETConsumer
{
    protected string $parameter;
    protected string $redirectURL;
    protected string $value;

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

    public function getParameterNames(): array
    {
        return array($this->parameter);
    }

    public function getValue() : string
    {
        return $this->value;
    }

    protected function process() : void
    {
        if (!isset($_GET[$this->parameter])) throw new Exception("Key: $this->parameter not received in GET");

        $this->value = (string)$_GET[$this->parameter];
    }
}
