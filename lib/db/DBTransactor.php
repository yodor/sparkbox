<?php
include_once("lib/forms/InputForm.php");
include_once("lib/beans/IDataBeanGetter.php");

class DBTransactor implements IDataBeanGetter
{

    protected $transact_values = NULL;

    protected $transaction_bean = NULL;
    protected $editID = -1;

    //external assigned
    protected $insert_values = NULL;
    protected $update_values = NULL;

    protected $lastID = -1;
    protected $form = NULL;

    protected $values = NULL;

    public function __construct()
    {
        $this->transact_values = array();
        $this->insert_values = array();
        $this->update_values = array();

    }

    public function getEditID()
    {
        return $this->editID;
    }

    public function getBean()
    {
        return $this->transaction_bean;
    }

    public function getLastID()
    {
        return $this->lastID;
    }

    public function appendValue($key, $val)
    {
        $this->transact_values[$key] = $val;
    }

    public function assignInsertValue($key, $val)
    {
        $this->insert_values[$key] = $val;
    }

    public function assignUpdateValue($key, $val)
    {
        $this->update_values[$key] = $val;
    }

    public function removeValue($key)
    {
        if (isset($this->transact_values[$key])) {

            unset($this->transact_values[$key]);
        }
    }

    public function getValue($key)
    {
        return $this->transact_values[$key];
    }

    public function getTransactionValues()
    {
        return $this->transact_values;
    }

    public function transactValues(InputForm $form)
    {
        $this->form = $form;

        debug("------------------------------------");
        debug("DBTransactor::transactValues | InputForm: " . get_class($form));

        foreach ($form->getFields() as $field_name => $field) {
            debug("DBTransactor::transactValues: field['$field_name']");
            if ($field->skip_transaction) {
                debug("DBTransactor::transactValues: field['$field_name'] skip_transaction flag is set");
                continue;
            }

            $field_transactor = $field->getValueTransactor();

            if ($field_transactor instanceof IDBFieldTransactor) {
                debug("DBTransactor::transactValues: field['$field_name'] Using transactor: " . get_class($field_transactor));
                $field->getValueTransactor()->transactValue($field, $this);
                debug("DBTransactor::transactValues: field['$field_name'] transacted");
            }
            else {

                debug("DBTransactor::transactValues: field['$field_name'] no suitable IDBFieldTransactor found. Skipping ...");
            }

        }

        debug("------------------------------------");
    }

    public function beforeCommit(DBTableBean $bean, DBDriver $db)
    {

        debug("DBTransactor::beforeCommit | DBTableBean: " . get_class($bean));
        if (!$this->form) throw new Exception("Expected InputForm is null");


        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        foreach ($this->form->getFields() as $field_name => $field) {


            $field_transactor = $field->getValueTransactor();

            if ($field_transactor instanceof IDBFieldTransactor) {
                debug("DBTransactor::beforeCommit: field['$field_name'] Using transactor: " . get_class($field_transactor));
                $field->getValueTransactor()->beforeCommit($field, $this, $db, $bean->key());
                debug("DBTransactor::beforeCommit: field['$field_name'] finished");
            }
            else {

                debug("DBTransactor::transactValues: field['$field_name'] no suitable IDBFieldTransactor found. Skipping ...");
            }

        }

    }

    public function afterCommit(DBTableBean $bean)
    {
        debug("DBTransactor::afterCommit | DBTableBean: " . get_class($bean));
        if (!$this->form) throw new Exception("Expected InputForm is null");

        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        foreach ($this->form->getFields() as $field_name => $field) {

            $field_transactor = $field->getValueTransactor();

            if ($field_transactor instanceof IDBFieldTransactor) {
                debug("DBTransactor::afterCommit: field['$field_name'] Using transactor: " . get_class($field_transactor));
                $field->getValueTransactor()->afterCommit($field, $this);
                debug("DBTransactor::afterCommit: field['$field_name'] finished");
            }
            else {
                debug("DBTransactor::afterCommit: field['$field_name'] no suitable IDBFieldTransactor found. Skipping ...");
            }

        }

    }


    public function processBean(DBTableBean $bean, $editID)
    {

        $this->transaction_bean = $bean;
        $this->editID = $editID;

        debug("DBTransactor::processBean | DBTableBean: " . get_class($bean));

        $db = DBDriver::Get();

        try {
            $db->transaction();

            debug("DBTransactor::processBean | Transaction started");

            $this->processBeanTransaction($bean, $db, $editID);

            $this->beforeCommit($bean, $db);

            if (is_callable("DBTransactor_onBeforeCommit")) {
                call_user_func("DBTransactor_onBeforeCommit", $bean, $db, $this->lastID, $this->values);
            }

            $db->commit();

            debug("DBTransactor::processImpl | Transaction Committed");

            $this->afterCommit($bean);

            if (is_callable("DBTransactor_onAfterCommit")) {
                call_user_func("DBTransactor_onAfterCommit", $bean, $db, $this->lastID, $this->values);
            }

            debug("DBTransactor::processImpl | STATUS_OK");
            //keypoint

        }
        catch (Exception $e) {

            $db->rollback();
            debug("DBTransactor::processImpl | Transaction rollback for error: " . $e->getMessage());

            $this->lastID = -1;
            throw $e;

        }

    }


    protected function processBeanTransaction(DBTableBean $bean, DBDriver $db, $editID)
    {
        debug("DBTransactor::processBeanTransaction | DBTableBean: " . get_class($bean) . " | EditID: $editID");

        $values = $this->mergeBeanValues($bean, $db, $editID);

        if ($editID > 0) {
            if (!$bean->update($editID, $values, $db)) throw new Exception("Unable to update: " . $db->getError());
            $this->lastID = $editID;
        }
        else {
            $this->lastID = $bean->insert($values, $db);
            if ($this->lastID < 1) throw new Exception("Unable to insert: " . $db->getError());
        }

        $this->values = $values;
    }

    protected function mergeBeanValues(DBTableBean $bean, DBDriver $db, $editID)
    {
        $values = array();
        if ($editID > 0) {
            $values = array_merge($this->transact_values, $this->update_values);
        }
        else {
            $values = array_merge($this->transact_values, $this->insert_values);
        }

        if (is_callable("DBTransactor_onMergeBeanValues")) {
            call_user_func_array("DBTransactor_onMergeBeanValues", array(&$values));

        }
        // 	  debugArray("Merged Values: ", $values);
        return $values;
    }
}

?>