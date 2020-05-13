<?php
include_once("input/DataInput.php");
include_once("db/BeanTransactor.php");

interface IDBFieldTransactor
{
    public function transactValue(BeanTransactor $transactor);

    public function beforeCommit(BeanTransactor $transactor, DBDriver $db, string $item_key);

    public function afterCommit(BeanTransactor $transactor);
}

?>
