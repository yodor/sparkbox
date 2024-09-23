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

    public function __construct(DBTableBean $bean, int $editID)
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

        foreach ($fieldNames as $fieldName) {
            $field = $form->getInput($fieldName);

            $proc = $field->getProcessor();

            debug("DataInput '$fieldName' using processor: " . get_class($proc));

            if ($proc->skip_transaction) {
                debug("InputProcessor 'skip_transaction' flag is set. Not calling transactValue()");
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
    public function beforeCommit(DBDriver $db)
    {

        if (!$this->form) throw new Exception("Expected InputForm is null");

        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        $names = $this->form->getInputNames();
        foreach ($names as $name) {

            $input = $this->form->getInput($name);

            $proc = $input->getProcessor();

            if ($proc->getTransactBean()) {

                $proc->beforeCommit($this, $db, $this->bean->key());

            }
            else {
                debug("DataInput '$name' does not use transact_bean");
            }
        }

    }

    public function afterCommit()
    {

        if (!$this->form) throw new Exception("Expected InputForm is null");

        //cycle all fields - do not skip fields with skip_transaction flag set. needed for cleanup
        $names = $this->form->getInputNames();
        foreach ($names as $name) {

            $input = $this->form->getInput($name);

            $input->getProcessor()->afterCommit($this);

        }

    }

    public function processBean()
    {

        $db = DBConnections::Open();

        try {
            $db->transaction();

            debug("DB Transaction started for bean: ".get_class($this->bean));

            $this->processBeanTransaction($db);

            $this->beforeCommit($db);

            SparkEventManager::emit(new BeanTransactorEvent(BeanTransactorEvent::BEFORE_COMMIT, $this, $db));

            $db->commit();

            debug("DB Transaction committed for bean: ".get_class($this->bean));

            try {
                $this->afterCommit();

                SparkEventManager::emit(new BeanTransactorEvent(BeanTransactorEvent::AFTER_COMMIT, $this, $db));
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

        $this->values = $values;

        //debug("Working bean: ".get_class($this->bean)." values: ",$this->values);

        if ($this->editID > 0) {
            debug("doing update");

            $this->bean->update($this->editID, $this->values, $db);
            $this->lastID = $this->editID;

        }
        else {
            debug("doing insert");

            $lastID = $this->bean->insert($this->values, $db);
            if ($lastID < 1) throw new Exception("Unable to insert: " . $db->getError());

            $this->lastID = $lastID;
        }

    }

}

?>
