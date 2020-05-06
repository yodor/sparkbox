<?php
include_once("lib/input/DataInput.php");
include_once("lib/db/DBTransactor.php");

interface IDBFieldTransactor
{
    public function transactValue(DataInput $input, DBTransactor $transactor);

    public function beforeCommit(DataInput $input, DBTransactor $transactor, DBDriver $db, $item_key);

    public function afterCommit(DataInput $input, DBTransactor $transactor);
}

?>
