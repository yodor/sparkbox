<?php
include_once("utils/URLParameter.php");

class DataParameter extends URLParameter
{

    protected $field;

    /**
     * DataParameter constructor.
     * @param string $param_name
     * @param string $field_name data row key name to use to set the value of this parameter
     */
    public function __construct(string $param_name, string $field_name = "")
    {
        if (!$field_name) {
            $field_name = $param_name;
        }
        $this->field = $field_name;

        parent::__construct($param_name, "");
    }

    public function field()
    {
        return $this->field;
    }

    public function setData(array $data)
    {
        if (isset($data[$this->field])) {
            $this->value = $data[$this->field];
        }
    }
}

?>