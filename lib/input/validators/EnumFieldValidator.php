<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class EnumFieldValidator implements IInputValidator
{

    protected array $enumValues = array();

    public function __construct(DBTableBean $bean, string $columnName)
    {
        $this->enumValues = DBEnumIterator::Enum2Array($bean->columnType($columnName));
    }

    public function validate(DataInput $input) : void
    {
        if (!in_array($input->getValue(), $this->enumValues)) {
            throw new Exception("Incorrect value");
        }
    }

}