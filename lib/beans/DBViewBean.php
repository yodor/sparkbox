<?php

include_once("beans/DBTableBean.php");

class DBViewBean extends DBTableBean
{
    public function __construct(string $table_name)
    {
        parent::__construct($table_name);
    }

    public function delete(int $id, ?DBDriver $db = NULL) : int
    {
        throw new Exception("View not writable");
    }

    public function deleteRef(string $column, string $value, ?DBDriver $db = NULL, array $keep_ids = array()) : int
    {
        throw new Exception("View not writable");
    }


    public function update($id, array $row, ?DBDriver $db = NULL) : int
    {
        throw new Exception("View not writable");
    }

    //

    public function insert(array $row, ?DBDriver $db = NULL): int
    {
        throw new Exception("View not writable");
    }

}