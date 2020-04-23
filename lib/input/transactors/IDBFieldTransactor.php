<?php
include_once("lib/input/DataInput.php");
include_once("lib/db/DBTransactor.php");

interface IDBFieldTransactor
{
    public function transactValue(DataInput $field, DBTransactor $transactor);

    public function beforeCommit(DataInput $field, DBTransactor $transactor, DBDriver $db, $item_key);

    public function afterCommit(DataInput $field, DBTransactor $transactor);
}

?>
