<?php
include_once("lib/iterators/ArrayDataIterator.php");

class DBEnumSelector extends ArrayDataIterator
{

    public function __construct(string $table_name, string $table_field)
    {
        $db = DBDriver::Get();

        $ret = $db->fieldType($this->table_name, $this->table_field);
        $ret = $db->enum2array($ret);

        $this->key = $this->table_field;

        parent::__construct($ret);
    }



}

?>