<?php
include_once("lib/iterators/ArrayDataIterator.php");

class DBEnumIterator extends ArrayDataIterator
{
    public function __construct(string $table_name, string $table_field)
    {
        $db = DBDriver::Get();

        $ret = $db->fieldType($table_name, $table_field);

        $ret = $db->enum2array($ret);

        parent::__construct($ret);
    }
}

?>