<?php
include_once("input/DataInput.php");
include_once("db/DBTransactor.php");

interface IDBFieldTransactor
{
    public function transactValue(DataInput $input, DBTransactor $transactor);

    public function beforeCommit(DataInput $input, DBTransactor $transactor, DBDriver $db, $item_key);

    public function afterCommit(DataInput $input, DBTransactor $transactor);
}

?>
