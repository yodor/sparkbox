<?php
include_once("input/processors/IBeanPostProcessor.php");
include_once("input/processors/IDBFieldTransactor.php");

/**
 * TODO: Write better description
 * Takes care to process the values posted or loaded from DBTableBean
 *
 * Class InputProcessor
 */
class InputProcessor implements IBeanPostProcessor, IDBFieldTransactor
{

    const TRANSACT_OBJECT = 2;
    const TRANSACT_VALUE = 3;

    public $transact_mode = InputProcessor::TRANSACT_VALUE;

    //does not want transactValue to be called if true
    public $skip_transaction = FALSE;

    //keeps map of storage_object UID to data_source primary key value
    protected $source_loaded_uids = array();

    //primary key values loaded from the DataInput's transact bean
    protected $source_loaded_keys = array();

    //foreign keys are posted in the order or values, if true will process them. used in direct mapping between fields.
    public $process_datasource_foreign_keys = FALSE;

    public $transact_empty_string_as_null = FALSE;

    //field source copy fields
    public $bean_copy_fields = array();

    //transacts additional values from renderer data source
    //matching by 'this' field name and its value posted. do not copy on empty 'value'
    public $renderer_source_copy_fields = array();

    //default is to use the DataInput name as transact field
    public $transact_field_name = "";

    /**
     * @var DataInput
     */
    protected $input;

    /**
     * @var DBTableBean
     */
    protected $transact_bean;

    public $accepted_tags;

    public function __construct(DataInput $input)
    {
        $this->input = $input;
        $this->input->setProcessor($this);

        $this->accepted_tags = DefaultAcceptedTags();
    }

    public function setTransactBean(DBTableBean $bean)
    {
        $this->transact_bean = $bean;
    }

    public function getTransactBean(): ?DBTableBean
    {
        return $this->transact_bean;
    }

    /**
     * DBTransactor calls this method just before commmit() on the main transaction
     * Populate additional fields in the transactor
     * @param BeanTransactor $transactor
     * @param DBDriver $db
     * @param string $item_key
     * @throws Exception
     */
    public function beforeCommit(BeanTransactor $transactor, DBDriver $db, string $item_key)
    {

        if (!$this->transact_bean) {
            debug("transact_bean is null - nothing to do in beforeCommit");
            return;
        }
        if (strcmp($this->transact_bean->getTableName(), $transactor->getBean()->getTableName()) == 0) {
            throw new Exception("Transact error: DataInput '" . $this->input->getName() . "' have transact bean is the same main transaction bean - '" . get_class($this->transact_bean) . "'");
        }

        $source_key = $this->transact_bean->key();
        $lastID = $transactor->getLastID();

        $field_name = $this->input->getName();

        debug("Using DataInput transact bean: " . get_class($this->transact_bean) . " | Field: $field_name | lastID: $lastID | Source PrimaryKey: $source_key | Field Values count: " . count($this->input->getValue()));

        $foreign_transacted = 0;

        if (count($this->input->getValue()) < 1) {

            debug("0 values to transact to data source. Clearing all rows of the data source: " . get_class($this->transact_bean));
            $this->transact_bean->deleteRef($item_key, $transactor->getLastID(), $db);

            return;
        }

        if ($this->transact_mode == InputProcessor::TRANSACT_VALUE) {
            debug("Transact Mode: TRANSACT_VALUE");
            // 	    $data_source->deleteRef($item_key, $lastID, $db);
            debug("Merging updated values ...");

            //TODO:try to update data source found in source_loaded_values. Delete removed values. Keep order of loaded
            //TODO: !!! merging is not really possible if there are unique constraints on the primary key and the foreign key as it tries to update before deleting the old key

            $processed_ids = array();

            debug("field values count: " . count($this->input->getValue()));
            // 	    debug("Post Values: ", $_POST);

            foreach ($this->input->getValue() as $idx => $value) {

                $dbrow = array();
                $dbrow[$item_key] = $transactor->getLastID();

                if (is_array($value)) throw new Exception("Could not transact value of type Array using transact mode TRANSACT_VALUE to data_source");
                if ($value instanceof StorageObject) throw new Exception("Could not transact value of type StorageObject using transact mode TRANSACT_VALUE to data_source");

                $dbrow[$field_name] = $value;

                //process posted foreign keys and assign them
                if ($this->process_datasource_foreign_keys) {
                    if (isset($_REQUEST["fk_$field_name"][$idx])) {
                        $fks = $_REQUEST["fk_$field_name"][$idx];
                        $fk_pairs = explode("|", $fks);
                        foreach ($fk_pairs as $fk_idx => $fk_pair) {
                            list($fk_name, $fk_value) = explode(":", $fk_pair);
                            $dbrow[$fk_name] = $fk_value;
                        }
                    }

                }

                //process bean copy fields
                if (is_array($this->bean_copy_fields) && count($this->bean_copy_fields) > 0) {
                    $bean_fields = $transactor->getValues();
                    foreach ($bean_fields as $key => $val) {
                        if (in_array($key, $this->bean_copy_fields)) {
                            $dbrow[$key] = $val;
                        }
                    }
                }

                $sourceID = array_shift($this->source_loaded_keys);
                if ($sourceID > 0) {
                    debug("DataSourceID: " . $sourceID);

                    $this->transact_bean->update($sourceID, $dbrow, $db);
                    //throw new Exception("Unable to update  data source bean. Error: " . $db->getError());
                    $processed_ids[] = $sourceID;
                }
                else {

                    $refID = $this->transact_bean->insert($dbrow, $db);
                    if ($refID < 1) throw new Exception("Unable to insert into data source bean. Error: " . $db->getError());
                    $processed_ids[] = $refID;
                }
                $foreign_transacted++;

            }

            //TODO:duplicate keys might get triggered
            debug("Deleting remaining transact bean values");
            $this->transact_bean->deleteRef($item_key, $transactor->getLastID(), $db, $processed_ids);

        }
        else if ($this->transact_mode == InputProcessor::TRANSACT_OBJECT) {

            debug("Transact mode: TRANSACT_OBJECT");

            debug("Current source loaded UIDs: ", $this->source_loaded_uids);

            $processed_ids = array();

            foreach ($this->input->getValue() as $idx => $value) {

                if (!($value instanceof StorageObject)) throw new Exception("TRANSACT_OBJECT support only StorageObject instances");

                $uid = $value->getUID();

                debug("Processing UID: $uid");

                if (!array_key_exists($uid, $this->source_loaded_uids)) {
                    debug("StorageObject UID: $uid not found in the loaded keys array. Will commit insert operation to data source ...");

                    //new value need insert
                    $dbrow = array();
                    $dbrow[$item_key] = $transactor->getLastID();

                    debug("Transact Mode: TRANSACT_OBJECT");
                    $dbrow[$field_name] = $db->escape(serialize($value));
                    debug("StorageObject UID: $uid stored as serialized value in the data source row ...");

                    $refID = $this->transact_bean->insert($dbrow, $db);
                    if ($refID < 1) throw new Exception("Unable to insert into data source bean. Error: " . $db->getError());
                    $foreign_transacted++;

                    $processed_ids[] = $refID;

                    debug("StorageObject UID: $uid transacted to data source with ID: " . $refID);
                }
                else {
                    //skip transaction. same uid
                    $processed_ids[] = $this->source_loaded_uids[$uid];
                }

            }

            debug("Processed data source keys dump: ", $processed_ids);

            //delete remaining values - datasource values with keys not found in processed_ids
            $this->transact_bean->deleteRef($item_key, $transactor->getLastID(), $db, $processed_ids);

            debug("Remaining data source keys removed");

        }
        else {
            throw new Exception("Unknown transact mode: " . $this->transact_mode);
        }
        debug("Total $foreign_transacted rows transacted to data source: " . get_class($this->transact_bean));

    }

    public function afterCommit(BeanTransactor $transactor)
    {
        //
    }

    //

    public function transactValue(BeanTransactor $transactor)
    {

        debug("DataInput: {$this->input->getName()}");

        switch ($this->transact_mode) {
            case InputProcessor::TRANSACT_VALUE:

                debug("Transact mode: TRANSACT_VALUE");

                $value = $this->input->getValue();

                if (!is_array($value)) {
                    if (strlen($value) == 0 && $this->transact_empty_string_as_null) {
                        $value = NULL;
                    }
                }

                if ($this->transact_field_name) {
                    $transactor->appendValue($this->transact_field_name, $value);
                }
                else {
                    $transactor->appendValue($this->input->getName(), $value);
                }

                //transacts additional values from renderer data source
                //matching by 'this' field name and its value posted. do not copy on empty 'value'
                if (is_array($this->renderer_source_copy_fields) && count($this->renderer_source_copy_fields) > 0 && $value) {
                    debug("renderer_source_copy_fields ... ");

                    $renderer = $this->input->getRenderer();
                    if (!$renderer instanceof DataIteratorField) throw new Exception("Renderer not instance of DataIteratorField");
                    $qry = $renderer->getIterator();
                    if (!($qry instanceof SQLQuery)) throw new Exception("Renderer iterator is not of type SQLQuery");

                    $qry->select->where()->add($renderer->getItemRenderer()->getValueKey(), "'$value'");
                    foreach ($this->renderer_source_copy_fields as $idx=>$field) {
                        $qry->select->fields()->set($field);
                    }
                    $qry->select->limit = 1;

                    debug("Using iterator SQL: " .$qry->select->getSQL());

                    $num = $qry->exec();

                    debug("Iterator results: $num");

                    if ($num < 1) throw new Exception("Renderer IDataIterator returned no results");

                    $row = $qry->next();
                    debug("Transacting renderer source fields to main transaction: ", $row);
                    foreach ($row as $field_name => $value) {
                        $transactor->appendValue($field_name, $value);
                    }
                }

                break;
            case InputProcessor::TRANSACT_OBJECT:
                throw new Exception("Unsupported TRANSACT_OBJECT for input field['" . $this->input->getName() . "']");
                break;
            default:
                throw new Exception("Unsupported transaction mode for input field['" . $this->input->getName() . "']");
        }

    }

    /**
     * BeanFormEditor calls this method to load the field data from bean fields
     * @param int $editID
     * @param DBTableBean $bean
     * @param DataInput $input
     * @param array $item_row
     * @throws Exception
     */
    public function loadBeanData(int $editID, DBTableBean $bean, array &$item_row)
    {
        $name = $this->input->getName();
        $item_key = $bean->key();

        debug("DataInput '$name' loading data from bean '" . get_class($bean) . "' - bean primary key is: $item_key");

        $values = array();

        if (array_key_exists($name, $item_row)) {

            debug("Key '$name' found in the bean result row - loading value");

            $value = $item_row[$name];

            if ($this->transact_mode == InputProcessor::TRANSACT_OBJECT) {
                //non required fields holdings storage objects can load NULL values, remove them as they dont need presentation
                if (!is_null($value)) {
                    $object = @unserialize($value);
                    if ($object !== FALSE) {
                        if (!($object instanceof StorageObject)) throw new Exception("Deserialized object is not StorageObject instance");
                        //

                        //tag with id and class
                        $object->id = $item_row[$bean->key()];
                        $object->className = get_class($bean);

                        $value = $object;
                    }
                }
            }

            //TODO: InputField::TRANSACT_OBJECT can be array ?
            if ($this->input instanceof ArrayDataInput) {
                $values = array($value);
            }
            else {
                $values = $value;
            }

        }
        else {

            //process data source values
            debug("Key '$name' not found in the bean result row - trying values from DataInput's transact bean");

            if (!$this->transact_bean) {
                debug("DataInput transact bean is NULL");
                $values = NULL;
            }
            else {

                $source_fields = $this->transact_bean->columnNames();
                $source_key = $this->transact_bean->key();

                debug("Loading values from transact bean '" . get_class($this->transact_bean) . "' with primary key '$source_key' ");

                if (!in_array($item_key, $source_fields)) throw new Exception("Key '$item_key' is not found in the transact bean fields");

                debug("Querying transact bean values by $item_key = $editID");

                $source_values = array();
                $qry = $this->transact_bean->queryFull();
                $qry->select->where()->add($item_key, $editID);
                $qry->exec();

                while ($row = $qry->next()) {
                    $source_values[] = $this->loadTargetBeanData($row, $source_key);
                }

                debug("Transact bean values loaded: ", $source_values);
                $values = $source_values;

            }

        }//array_key_exists

        if (is_array($values)) {
            debug("Setting value: ", $values);
        }
        else {
            debug("Setting value: $values");
        }
        $this->input->setValue($values);

    }

    protected function loadTargetBeanData(array &$item_row, string $source_key)
    {
        $value = NULL;
        $field_name = $this->input->getName();

        if ($this->transact_mode == InputProcessor::TRANSACT_VALUE) {

            $value = $item_row[$field_name];
            $this->source_loaded_keys[] = $item_row[$source_key];
            //debug("SourceLoaded value for datasource keyID: " . $item_row[$source_key]);

        }
        else if ($this->transact_mode == InputProcessor::TRANSACT_OBJECT) {

            $value = $item_row[$field_name];

            $storage_object = @unserialize($value);
            if ($storage_object !== FALSE) {
                if (!($storage_object instanceof StorageObject)) throw new Exception("Deserialized object is not a StorageObject.");

                //tag with id and class
                $storage_object->id = $item_row[$source_key];
                $storage_object->className = get_class($this->transact_bean);

                $value = $storage_object;

                $uid = $value->getUID();

                $source_key_value = $item_row[$source_key];

                $this->source_loaded_uids[$uid] = $source_key_value;

                debug("Source Load UID:  Deserialized StorageObject UID: $uid From data source $source_key='$source_key_value'");

            }
            else {
                if (!is_null($value)) throw new Exception("Expected serialized contents in '$field_name' of data source: " . get_class($this->transact_bean));
            }
        }
        return $value;
    }

    /**
     * Load value using arr as input
     * Match input name to array key name and set the value to this datainput from it
     * @param array $data Posted data array
     */
    public function loadPostData(array &$data)
    {

        $name = $this->input->getName();

        //sanitize non-compound fields
        if (array_key_exists($name, $data)) {

            $value = $data[$name];

            $value = sanitizeInput($value, $this->accepted_tags);

            $this->input->setValue($value);

        }
        else {
            $this->input->clear();
        }

    }

    public function clearURLParameters(URLBuilder $url)
    {
        $url->remove($this->input->getName());
    }

}

?>