<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class EnumFieldValidator implements IInputValidator
{
    protected string $table_name;
    protected string $field_name;

    public function __construct(string $table_name, string $field_name = "")
    {
        $this->table_name = $table_name;
        $this->field_name = $field_name;
    }

    public function validate(DataInput $input) : void
    {
        if (!$this->field_name) $this->field_name = $input->getName();

        $db = DBConnections::Open();
        $ret = $db->fieldType($this->table_name, $this->field_name);
        $ret = $db->Enum2Array($ret);

        if (!in_array($input->getValue(), $ret)) {

            throw new Exception("Incorrect value");

        }
    }

}