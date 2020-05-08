<?php
include_once("iterators/ArrayDataIterator.php");

class DBEnumIterator extends ArrayDataIterator
{
    public function __construct(string $table_name, string $table_field)
    {
        $db = DBConnections::Get();

        $ret = $db->fieldType($table_name, $table_field);

        $ret = $db->Enum2Array($ret);

        parent::__construct($ret);
    }
}

?>