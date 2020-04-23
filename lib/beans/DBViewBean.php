<?php

include_once("lib/beans/DBTableBean.php");

class DBViewBean extends DBTableBean
{
    public function __construct($table_name)
    {
        parent::__construct($table_name);
    }

    public function deleteID($id, $db = false)
    {
        throw new Exception("View not writable");
    }

    public function deleteRef($refkey, $refval, $db = false, $keep_ids = array())
    {
        throw new Exception("View not writable");
    }

    public function toggleField($id, $field)
    {
        throw new Exception("View not writable");
    }

    public function update($id, &$row, &$db = false)
    {
        throw new Exception("View not writable");
    }

    //

    public function insert(&$row, &$db = false)
    {
        throw new Exception("View not writable");
    }


}

?>
