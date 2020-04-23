<?php
include_once("lib/input/transactors/IDBFieldTransactor.php");
include_once("lib/input/DataInput.php");
include_once("lib/db/DBTransactor.php");

class CustomFieldTransactor implements IDBFieldTransactor
{

    public $transact_field_name = "";

    public function __construct($transact_field_name)
    {
        $this->transact_field_name = $transact_field_name;
    }

    public function beforeCommit(DataInput $field, DBTransactor $transactor, DBDriver $db, $item_key)
    {


    }

    public function afterCommit(DataInput $field, DBTransactor $transactor)
    {

    }

    public function transactValue(DataInput $field, DBTransactor $transactor)
    {
        if ($this->transact_field_name) {
            $transactor->appendValue($this->transact_field_name, $field->getValue());

        }
        else {
            debug("CustomFieldTransactor::transactValue: Not transacting field['" . $field->getName() . "'] with empty transact_field_name");

        }
    }

}

?>