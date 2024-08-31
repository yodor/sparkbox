<?php
include_once("db/BeanTransactor.php");
include_once("dbdriver/DBDriver.php");

interface IDBFieldTransactor
{
    public function transactValue(BeanTransactor $transactor) : void;

    public function beforeCommit(BeanTransactor $transactor, DBDriver $db, string $item_key) : void;

    public function afterCommit(BeanTransactor $transactor) : void;

    public function setTargetColumn(string $name) : void;


}

?>
