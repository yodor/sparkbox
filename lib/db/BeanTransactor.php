<?php
include_once("objects/SparkEvent.php");
include_once("objects/SparkObserver.php");
include_once("forms/InputForm.php");
include_once("beans/IBeanEditor.php");
include_once("objects/events/BeanTransactorEvent.php");

/**
 * Process all DataInput controls from an InputForm and prepare values to be stored in a DBTableBean
 * Allows data to be stored into other DBTableBeans as set from the form DataInput fields
 * Handles add data to 'DBTableBean' and edit data from 'DBTableBean'
 * Class BeanTransactor
 */
class BeanTransactor extends SparkObject implements IBeanEditor
{

    protected array $values = array();

    /**
     * @var DBTableBean
     */
    protected DBTableBean $bean;

    /**
     * @var int
     */
    protected int $editID = -1;

    //external assigned
    protected array $insert_values = array();
    protected array $update_values = array();

    /**
     * @var int
     */
    protected int $lastID = -1;
    /**
     * @var InputForm|null
     */
    protected ?InputForm $form;

    public function __construct(DBTableBean $bean, int $editID=-1)
    {
        parent::__construct();

        $this->values = array();
        $this->insert_values = array();
        $this->update_values = array();

        $this->form = null;
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

    /**
     * Add value to this transactor values. Will be commited with the main transaction
     * @param string $key
     * @param $val
     */
    public function appendValue(string $key, $val) : void
    {
        $this->values[$key] = $val;
    }

    public function appendURLParameter(URLParameter $param) : void
    {
        $this->values[$param->name()] = $param->value();
    }

    /**
     * Add value '$val' using key name '$key' only during insert operation
     * @param string $key
     * @param $val
     */
    public function assignInsertValue(string $key, $val) : void
    {
        $this->insert_values[$key] = $val;
    }

    public function assignUpdateValue(string $key, $val) : void
    {
        $this->update_values[$key] = $val;
    }

    public function removeValue(string $key) : void
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

    public function processForm(InputForm $form) : void
    {
        $this->form = $form;

        Debug::ErrorLog("Using InputForm: " . get_class($form));

        $fieldNames = $form->inputNames();

        foreach ($fieldNames as $fieldName) {
            $field = $form->getInput($fieldName);

            $proc = $field->getProcessor();

            Debug::ErrorLog("DataInput '$fieldName' using processor: " . get_class($proc));

            if ($proc->skip_transaction) {
                Debug::ErrorLog("InputProcessor 'skip_transaction' flag is set. Not calling transactValue()");
                continue;
            }

            $proc->transactValue($this);

        }

    }

    /**
     *
     * @param DBDriver $db
     * @throws Exception
     */
    public function beforeCommit(DBDriver $db) : void
    {

        if (!$this->form) throw new Exception("Expected InputForm is null");

        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        $names = $this->form->inputNames();
        foreach ($names as $name) {

            $input = $this->form->getInput($name);

            $proc = $input->getProcessor();

            if ($proc->getTransactBean()) {

                $proc->beforeCommit($this, $db, $this->bean->key());

            }
            else {
                Debug::ErrorLog("DataInput '$name' does not use transact_bean");
            }
        }

    }

    public function afterCommit() : void
    {

        if (!$this->form) throw new Exception("Expected InputForm is null");

        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        $names = $this->form->inputNames();
        foreach ($names as $name) {

            $input = $this->form->getInput($name);

            $input->getProcessor()->afterCommit($this);

        }

    }

    public function processBean() : void
    {

        $db = DBConnections::Open();

        try {
            $db->transaction();

            Debug::ErrorLog("DB Transaction started for bean: ".get_class($this->bean));

            $this->processBeanTransaction($db);

            $this->beforeCommit($db);

            SparkEventManager::emit(new BeanTransactorEvent(BeanTransactorEvent::BEFORE_COMMIT, $this, $db));

            $db->commit();

            Debug::ErrorLog("DB Transaction committed for bean: ".get_class($this->bean));

            try {
                $this->afterCommit();

                SparkEventManager::emit(new BeanTransactorEvent(BeanTransactorEvent::AFTER_COMMIT, $this, $db));
            }
            catch (Exception $exx) {
                Debug::ErrorLog("afterCommit() failed: " . $exx->getMessage());
            }

        }
        catch (Exception $e) {

            $db->rollback();
            Debug::ErrorLog("DB Transaction rollback for error: " . $e->getMessage());

            $this->lastID = -1;
            throw $e;
        }

    }

    /**
     * Do update or insert on the DBTableBean using $this->values
     * @param DBDriver $db
     * @throws Exception
     */
    protected function processBeanTransaction(DBDriver $db) : void
    {

        Debug::ErrorLog("EditID: $this->editID");
        $values = array();
        if ($this->editID > 0) {
            $values = array_merge($this->values, $this->update_values);
        }
        else {
            $values = array_merge($this->values, $this->insert_values);
        }

        $this->values = $values;

        //Debug::ErrorLog("Working bean: ".get_class($this->bean)." values: ",$this->values);

        if ($this->editID > 0) {
            Debug::ErrorLog("doing update");

            $this->bean->update($this->editID, $this->values, $db);
            $this->lastID = $this->editID;

        }
        else {
            Debug::ErrorLog("doing insert");

            $lastID = $this->bean->insert($this->values, $db);
            if ($lastID < 1) throw new Exception("Unable to insert: " . $db->getError());

            $this->lastID = $lastID;
        }

    }

}