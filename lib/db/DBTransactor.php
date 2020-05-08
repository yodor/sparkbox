<?php
include_once("forms/InputForm.php");
include_once("beans/IBeanEditor.php");

class DBTransactor implements IBeanEditor
{

    protected $transact_values = array();

    /**
     * @var DBTableBean
     */
    protected $transaction_bean = NULL;

    /**
     * @var int
     */
    protected $editID = -1;

    //external assigned
    protected $insert_values = array();
    protected $update_values = array();

    /**
     * @var int
     */
    protected $lastID = -1;
    /**
     * @var InputForm
     */
    protected $form = NULL;

    protected $values = NULL;

    public function __construct()
    {
        $this->transact_values = array();
        $this->insert_values = array();
        $this->update_values = array();

    }

    public function getEditID(): int
    {
        return $this->editID;
    }

    public function getBean(): DBTableBean
    {
        return $this->transaction_bean;
    }

    public function setEditID(int $editID): void
    {
        $this->editID = $editID;
    }

    public function setBean(DBTableBean $bean): void
    {
        $this->bean = $bean;
    }

    public function getLastID(): int
    {
        return $this->lastID;
    }

    public function appendValue(string $key, $val)
    {
        $this->transact_values[$key] = $val;
    }

    public function assignInsertValue(string $key, $val)
    {
        $this->insert_values[$key] = $val;
    }

    public function assignUpdateValue(string $key, $val)
    {
        $this->update_values[$key] = $val;
    }

    public function removeValue(string $key)
    {
        if (isset($this->transact_values[$key])) {

            unset($this->transact_values[$key]);
        }
    }

    public function getValue(string $key)
    {
        return $this->transact_values[$key];
    }

    public function getTransactionValues(): array
    {
        return $this->transact_values;
    }

    public function transactValues(InputForm $form)
    {
        $this->form = $form;


        debug("Using form: " . get_class($form));

        $fieldNames = $form->getInputNames();

        foreach ($fieldNames as $pos => $fieldName) {
            $field = $form->getInput($fieldName);

            if ($field->skip_transaction) {
                debug("field['$fieldName'] skip_transaction flag is set");
                continue;
            }

            $field_transactor = $field->getValueTransactor();

            if ($field_transactor instanceof IDBFieldTransactor) {
                debug("field['$fieldName'] Using transactor: " . get_class($field_transactor));
                $field->getValueTransactor()->transactValue($field, $this);
            }
            else {
                debug("field['$fieldName'] no suitable IDBFieldTransactor found. Skipping ...");
            }

        }

    }

    public function beforeCommit(DBTableBean $bean, DBDriver $db)
    {

        debug("DBTableBean: " . get_class($bean));
        if (!$this->form) throw new Exception("Expected InputForm is null");

        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        $fieldNames = $this->form->getInputNames();
        foreach ($fieldNames as $pos => $fieldName) {

            $field = $this->form->getInput($fieldName);

            $transactor = $field->getValueTransactor();

            if ($transactor instanceof IDBFieldTransactor) {
                debug("field['$fieldName'] Using transactor: " . get_class($transactor));
                $field->getValueTransactor()->beforeCommit($field, $this, $db, $bean->key());
            }
            else {
                debug("field['$fieldName'] no suitable IDBFieldTransactor found. Skipping ...");
            }

        }

    }

    public function afterCommit(DBTableBean $bean)
    {
        debug("DBTableBean: " . get_class($bean));
        if (!$this->form) throw new Exception("Expected InputForm is null");

        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        $fieldNames = $this->form->getInputNames();
        foreach ($fieldNames as $pos => $fieldName) {

            $field = $this->form->getInput($fieldName);

            $transactor = $field->getValueTransactor();

            if ($transactor instanceof IDBFieldTransactor) {
                debug("field['$fieldName'] Using transactor: " . get_class($transactor));
                $field->getValueTransactor()->afterCommit($field, $this);
            }
            else {
                debug("field['$fieldName'] no suitable IDBFieldTransactor found. Skipping ...");
            }

        }

    }


    public function processBean(DBTableBean $bean, int $editID)
    {

        $this->transaction_bean = $bean;
        $this->editID = $editID;

        debug("DBTableBean: " . get_class($bean));

        $db = DBConnections::Factory();

        try {
            $db->transaction();

            debug("DB Transaction started");

            $this->processBeanTransaction($bean, $db, $editID);

            $this->beforeCommit($bean, $db);

            if (is_callable("DBTransactor_onBeforeCommit")) {
                call_user_func("DBTransactor_onBeforeCommit", $bean, $db, $this->lastID, $this->values);
            }

            $db->commit();

            debug("DB Transaction Committed");

            $this->afterCommit($bean);

            if (is_callable("DBTransactor_onAfterCommit")) {
                call_user_func("DBTransactor_onAfterCommit", $bean, $db, $this->lastID, $this->values);
            }

            debug("STATUS_OK");
            //keypoint

        }
        catch (Exception $e) {

            $db->rollback();
            debug("DB Transaction rollback for error: " . $e->getMessage());

            $this->lastID = -1;
            throw $e;

        }

    }


    protected function processBeanTransaction(DBTableBean $bean, DBDriver $db, int $editID)
    {
        debug("DBTableBean: " . get_class($bean) . " | editID: $editID");

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

    protected function mergeBeanValues(DBTableBean $bean, DBDriver $db, int $editID)
    {
        debug("DBTableBean: " . get_class($bean) . " | editID: $editID");
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
        // 	  debug("Merged Values: ", $values);
        return $values;
    }
}

?>