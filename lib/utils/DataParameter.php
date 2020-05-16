<?php
include_once("utils/URLParameter.php");

class DataParameter extends URLParameter
{

    public function __construct(string $name, string $value = "")
    {

        if (!$value) {
            $this->field = $name;
        }
        else {
            $this->field = $value;
        }

        parent::__construct($name, $value);

    }

}
?>