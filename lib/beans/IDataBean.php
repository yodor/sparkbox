<?php

interface IDataBean
{
    public function getCount() : int;

    public function fields() : array;

    public function query() : SQLQuery;
    //public function startIterator($filter = "", $fields = "");

    //public function fetchNext(array &$row, $iterator = false) : bool;

    public function deleteID(int $id);

    public function getByID(int $id);

    public function getByRef($refkey, $refid);

    public function deleteRef($refkey, $refval);

    public function haveField(string $field_name) : bool;

    public function key();

    //public function startFieldIterator($filter_field, $filter_value);

}

?>