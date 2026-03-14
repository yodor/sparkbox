<?php
include_once("iterators/ArrayDataIterator.php");

class DBEnumIterator extends ArrayDataIterator
{
    public function __construct(DBTableBean $bean, string $columnName)
    {
        $columnType = $bean->columnType($columnName);
        parent::__construct(DBEnumIterator::Enum2Array($columnType));
    }

    public static function Enum2Array(string $enum_type) : array
    {
        $enum_type = str_replace("enum(", "", $enum_type);
        $enum_type = str_replace(")", "", $enum_type);
        $enum_type = str_replace("'", "", $enum_type);

        return explode(",", $enum_type);
    }
}