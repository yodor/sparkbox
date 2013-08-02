<?php
include_once("lib/input/InputField.php");
include_once("lib/db/DBTransactor.php");

interface IDBFieldTransactor
{
  public function transactValue(InputField $field, DBTransactor $transactor);
  public function beforeCommit(InputField $field, DBTransactor $transactor, DBDriver $db, $item_key);
  public function afterCommit(InputField $field, DBTransactor $transactor);
}

?>
