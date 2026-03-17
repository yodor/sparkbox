<?php
include_once("utils/url/URLParameter.php");

class DataParameter extends URLParameter
{

    /**
     * Data binding key name
     * @var string
     */
    protected string $bindingKey = "";

    /**
     * Construct the url query parameter
     * @param string $name  Set the url query parameter name to '$name' ex. ?$name=
     * @param string $field Data result field key name. If empty use '$name' as field key name
     */
    public function __construct(string $name, string $bindingKey = "")
    {
        parent::__construct($name);

        if (!$bindingKey) {
            $this->bindingKey = $name;
        }
        else {
            $this->bindingKey = $bindingKey;
        }
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        if ($this->bindingKey) {
            if (isset($data[$this->bindingKey])) {
                $this->value = $data[$this->bindingKey];
            }
        }
    }
}