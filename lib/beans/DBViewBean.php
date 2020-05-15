<?php

include_once("beans/DBTableBean.php");

class DBViewBean extends DBTableBean
{
    public function __construct(string $table_name)
    {
        parent::__construct($table_name);
    }

    public function deleteID(int $id, DBDriver $db = NULL)
    {
        throw new Exception("View not writable");
    }

    public function deleteRef($refkey, $refval, $db = FALSE, $keep_ids = array())
    {
        throw new Exception("View not writable");
    }

    public function toggleField(int $id, string $field)
    {
        throw new Exception("View not writable");
    }

    public function update($id, array &$row, DBDriver $db = NULL)
    {
        throw new Exception("View not writable");
    }

    //

    public function insert(array &$row, DBDriver $db = NULL): int
    {
        throw new Exception("View not writable");
    }

}

?>
