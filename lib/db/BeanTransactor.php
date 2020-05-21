<?php
include_once("forms/InputForm.php");
include_once("beans/IBeanEditor.php");

/**
 * Process all DataInput controls from an InputForm and prepare values to be stored in a DBTableBean
 * Allows data to be stored into other DBTableBeans as set from the form DataInput fields
 * Handles add data to 'DBTableBean' and edit data from 'DBTableBean'
 * Class BeanTransactor
 */
class BeanTransactor implements IBeanEditor
{

    protected $values = array();

    /**
     * @var DBTableBean
     */
    protected $bean = NULL;

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

    public function __construct(DBTableBean $bean, int $editID)
    {
        $this->values = array();
        $this->insert_values = array();
        $this->update_values = array();

        $this->bean = $bean;
        $this->editID = $editID;
    }

    public function getEditID(): int
    {
        return $this->editID;
    }

    public function getBean(): DBTableBean
    {
        return $this->bean;
    }

    public function setEditID(int $editID)
    {
        $this->editID = $editID;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
    }

    public function getLastID(): int
    {
        return $this->lastID;
    }

    /**
     * Add value to this transactor values. Will be commited with the main transaction
     * @param string $key
     * @param $val
     */
    public function appendValue(string $key, $val)
    {
        $this->values[$key] = $val;
    }

    public function appendURLParameter(URLParameter $param)
    {
        $this->values[$param->name()] = $param->value();
    }

    /**
     * Add value '$val' using key name '$key' only during insert operation
     * @param string $key
     * @param $val
     */
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
        if (isset($this->values[$key])) {

            unset($this->values[$key]);
        }
    }

    public function getValue(string $key)
    {
        return $this->values[$key];
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function processForm(InputForm $form)
    {
        $this->form = $form;

        debug("Using InputForm: " . get_class($form));

        $fieldNames = $form->getInputNames();

        foreach ($fieldNames as $pos => $fieldName) {
            $field = $form->getInput($fieldName);

            $proc = $field->getProcessor();

            if ($proc) {

                debug("DataInput ['$fieldName'] Using processor: " . get_class($proc));

                if ($proc->skip_transaction) {
                    debug("InputProcessor has the skip_transaction flag is set. Not calling transactValue()");
                    continue;
                }

                $proc->transactValue($this);
            }
            else {
                debug("DataInput ['$fieldName'] no input processor set - Skipping ...");
            }

        }

    }

    public function beforeCommit(DBDriver $db)
    {

        debug("Using DBTableBean: " . get_class($this->bean));
        if (!$this->form) throw new Exception("Expected InputForm is null");

        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        $fieldNames = $this->form->getInputNames();
        foreach ($fieldNames as $pos => $fieldName) {

            $field = $this->form->getInput($fieldName);

            $proc = $field->getProcessor();

            if ($proc) {
                debug("DataInput ['$fieldName'] Using transactor: " . get_class($proc));
                $proc->beforeCommit($this, $db, $this->bean->key());
            }
            else {
                debug("DataInput ['$fieldName'] transactor is not set. Skipping ...");
            }

        }

    }

    public function afterCommit()
    {
        debug("DBTableBean: " . get_class($this->bean));
        if (!$this->form) throw new Exception("Expected InputForm is null");

        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        $fieldNames = $this->form->getInputNames();
        foreach ($fieldNames as $pos => $fieldName) {

            $field = $this->form->getInput($fieldName);

            $proc = $field->getProcessor();

            if ($proc) {
                debug("DataInput ['$fieldName'] Using transactor: " . get_class($proc));
                $proc->afterCommit($this);
            }
            else {
                debug("DataInput ['$fieldName'] transactor is not set. Skipping ...");
            }

        }

    }

    public function processBean()
    {

        debug("Using bean: " . get_class($this->bean));

        $db = DBConnections::Factory();

        try {
            $db->transaction();

            debug("DB Transaction Started");

            $this->processBeanTransaction($db);

            $this->beforeCommit($db);

            if (is_callable("DBTransactor_onBeforeCommit")) {
                call_user_func("DBTransactor_onBeforeCommit", $this->bean, $db, $this->lastID, $this->values);
            }

            $db->commit();

            debug("DB Transaction Committed");

            try {
                $this->afterCommit();

                if (is_callable("DBTransactor_onAfterCommit")) {
                    call_user_func("DBTransactor_onAfterCommit", $this->bean, $db, $this->lastID, $this->values);
                }
            }
            catch (Exception $exx) {
                debug("afterCommit() failed: " . $exx->getMessage());
            }

        }
        catch (Exception $e) {

            $db->rollback();
            debug("DB Transaction rollback for error: " . $e->getMessage());

            $this->lastID = -1;
            throw $e;
        }

    }

    /**
     * Do update or insert on the DBTableBean using $this->values
     * @param DBDriver $db
     * @throws Exception
     */
    protected function processBeanTransaction(DBDriver $db)
    {

        debug("EditID: $this->editID");
        $values = array();
        if ($this->editID > 0) {
            $values = array_merge($this->values, $this->update_values);
        }
        else {
            $values = array_merge($this->values, $this->insert_values);
        }

        if (is_callable("DBTransactor_onMergeBeanValues")) {
            call_user_func_array("DBTransactor_onMergeBeanValues", array(&$values));
        }

        $this->values = $values;

        if ($this->editID > 0) {
            debug("doing update: ", $this->values);

            $this->bean->update($this->editID, $this->values, $db);
            $this->lastID = $this->editID;

        }
        else {
            debug("doing insert");

            $lastID = $this->bean->insert($this->values, $db);
            if ($lastID < 1) {

                throw new Exception("Unable to insert: " . $this->bean->getError());
            }
            $this->lastID = $lastID;
        }

    }

}

?>