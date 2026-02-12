<?php
include_once("utils/url/URLParameter.php");

class DataParameter extends URLParameter
{

    /**
     * Construct the url query parameter
     * @param string $name  Set the url query parameter name to '$name' ex. ?$name=
     * @param string $field Data result field key name. If empty use '$name' as field key name
     */
    public function __construct(string $name, string $field = "")
    {

        if (!$field) {
            $this->field = $name;
        }
        else {
            $this->field = $field;
        }

        parent::__construct($name, $field);

    }

}