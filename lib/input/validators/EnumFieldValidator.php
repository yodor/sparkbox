<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/DataInput.php");

class EnumFieldValidator implements IInputValidator
{
    protected $table_name;
    protected $field_name;

    public function __construct($table_name, $field_name = NULL)
    {
        $this->table_name = $table_name;
        $this->field_name = $field_name;
    }

    public function validateInput(DataInput $field)
    {
        if (!$this->field_name) $this->field_name = $field->getName();

        $ret = DBDriver::Get()->fieldType($this->table_name, $this->field_name);
        $ret = DBDriver::Get()->enum2array($ret);

        if (!in_array($field->getValue(), $ret)) {

            throw new Exception("Incorrect value");

        }
    }

}

?>