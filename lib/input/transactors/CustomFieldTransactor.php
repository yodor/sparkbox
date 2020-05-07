<?php
include_once("input/transactors/IDBFieldTransactor.php");
include_once("input/DataInput.php");
include_once("db/DBTransactor.php");

class CustomFieldTransactor implements IDBFieldTransactor
{

    public $transact_field_name = "";

    public function __construct(string $transact_field_name)
    {
        $this->transact_field_name = $transact_field_name;
    }

    public function beforeCommit(DataInput $input, DBTransactor $transactor, DBDriver $db, $item_key)
    {


    }

    public function afterCommit(DataInput $input, DBTransactor $transactor)
    {

    }

    public function transactValue(DataInput $input, DBTransactor $transactor)
    {
        if ($this->transact_field_name) {
            $transactor->appendValue($this->transact_field_name, $input->getValue());

        }
        else {
            debug("Not transacting field['" . $input->getName() . "'] with empty transact_field_name");

        }
    }

}

?>