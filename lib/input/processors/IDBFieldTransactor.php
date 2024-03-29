<?php
include_once("db/BeanTransactor.php");
include_once("dbdriver/DBDriver.php");

interface IDBFieldTransactor
{
    public function transactValue(BeanTransactor $transactor);

    public function beforeCommit(BeanTransactor $transactor, DBDriver $db, string $item_key);

    public function afterCommit(BeanTransactor $transactor);

    public function setTargetColumn(string $name);


}

?>