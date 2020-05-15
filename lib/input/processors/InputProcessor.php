<?php
include_once("input/processors/IBeanPostProcessor.php");
include_once("input/processors/IDBFieldTransactor.php");

/**
 * TODO: Write better description
 * Takes care to to process the values posted or loaded from DBTableBean
 *
 * Class InputProcessor
 */
class InputProcessor implements IBeanPostProcessor, IDBFieldTransactor
{

    //transact DBROW without source is incompatible with non required field.
    const TRANSACT_DBROW = 1;
    const TRANSACT_OBJECT = 2;
    const TRANSACT_VALUE = 3;

    public $transact_mode = InputProcessor::TRANSACT_VALUE;

    //does not want transactValue to be called if true
    public $skip_transaction = FALSE;

    //keeps map of storage_object UID to data_source primary key value
    protected $source_loaded_uids = array();
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

    public function getTransactBean() : ?DBTableBean
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
            debug("transact_bean is null  nothing to do in beforeCommit");
            return;
        }
        if (strcmp($this->transact_bean->getTableName(), $transactor->getBean()->getTableName())==0) {
            throw new Exception("Field transact error for field: '" . $this->input->getName() . "'. Data source bean and main transaction bean are the same - '" . get_class($this->transact_bean) . "'");
        }

        $source_key = $this->transact_bean->key();
        $lastID = $transactor->getLastID();

        $field_name = $this->input->getName();

        debug("Using data Source: " . get_class($this->transact_bean) . " | Field: $field_name | lastID: $lastID | Source PrimaryKey: $source_key | Field Values count: " . count($this->input->getValue()));

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

                    if (!$this->transact_bean->update($sourceID, $dbrow, $db)) throw new Exception("Unable to update  data source bean. Error: " . $db->getError());
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
            $this->transact_bean->deleteRef($item_key, $transactor->getLastID(), $db, $processed_ids);

        }
        else {

            debug("Processing for non TRANSACT_VALUE modes ... ");

            debug("Current source loaded UIDs dump: ", $this->source_loaded_uids);

            $processed_ids = array();

            foreach ($this->input->getValue() as $idx => $value) {

                if (!($value instanceof StorageObject)) throw new Exception("Transact mode TRANSACT_DBROW can transact only values of type  StorageObject");

                $uid = $value->getUID();

                debug("Processing UID: $uid");

                if (!array_key_exists($uid, $this->source_loaded_uids)) {
                    debug("StorageObject UID: $uid not found in the loaded keys array. Will commit insert operation to data source ...");

                    //new value need insert
                    $dbrow = array();
                    $dbrow[$item_key] = $transactor->getLastID();

                    if ($this->transact_mode == InputProcessor::TRANSACT_DBROW) {
                        debug("Transact Mode: TRANSACT_DBROW");
                        $value->setDataKey($field_name);
                        $value->deconstruct($dbrow);

                        debug("StorageObject UID: $uid deconstructed as fields in the data source row ...");

                    }
                    else if ($this->transact_mode == InputProcessor::TRANSACT_OBJECT) {
                        debug("Transact Mode: TRANSACT_OBJECT");
                        $dbrow[$field_name] = $db->escape(serialize($value));
                        debug("StorageObject UID: $uid stored as serialized value in the data source row ...");
                    }
                    else {
                        throw new Exception("Unknown transact mode: " . $this->transact_mode);
                    }

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

                    $qry->select->where = $renderer->getItemRenderer()->getValueKey() . "='$value'";
                    $qry->select->fields = implode(", ", $this->renderer_source_copy_fields);
                    $qry->select->limit = " 1 ";
                    $num = $qry->exec();
                    debug("Iterator: " . $qry->name() . " WHERE: {$qry->select->where} returned #$num results");
                    if ($num < 1) throw new Exception("Renderer IDataIterator returned no results");

                    $row = $qry->next();
                    debug("Transacting renderer source fields to main transaction: ", $row);
                    foreach ($row as $field_name => $value) {
                        $transactor->appendValue($field_name, $value);
                    }
                }

                break;
            case InputProcessor::TRANSACT_DBROW:
                throw new Exception("Unsupported TRANSACT_DBROW for input field['" . $this->input->getName() . "']");
                break;
            case InputProcessor::TRANSACT_OBJECT:
                throw new Exception("Unsupported TRANSACT_OBJECT for input field['" . $this->input->getName() . "']");
                break;
            default:
                throw new Exception("Unsupported transaction mode for input field['" . $this->input->getName() . "']");
        }

    }

    protected function processRowData(array &$item_row, string $source_key)
    {
        $value = NULL;
        $field_name = $this->input->getName();

        if ($this->transact_mode == InputProcessor::TRANSACT_VALUE) {

            $value = $item_row[$field_name];
            $this->source_loaded_keys[] = $item_row[$source_key];
            debug("SourceLoaded value for datasource keyID: " . $item_row[$source_key]);

        }
        if ($this->transact_mode == InputProcessor::TRANSACT_DBROW) {

            $value = StorageObject::reconstruct($item_row, $field_name);
            $uid = $value->getUID();

            $source_key_value = $item_row[$source_key];

            $this->source_loaded_uids[$uid] = $source_key_value;

            debug("Source Load UID: Reconstructed StorageObject UID: $uid From data source $source_key='$source_key_value'");

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
        debug("Using DataInput [$name] with DBTableBean: " . get_class($bean) . " | Primary Key: $item_key");

        $values = array();

        if (array_key_exists($name, $item_row)) {

            debug("Key '$name' found in the bean result row - loading value");

            $value = $item_row[$name];

            if ($this->transact_mode == InputProcessor::TRANSACT_OBJECT) {
                //non required fields holdings storage objects can load NULL values, remove them as they dont need presentation
                if (!is_null($value)) {
                    $object = @unserialize($value);
                    if ($object !== FALSE) {
                        if (!($object instanceof StorageObject)) throw new Exception("Deserialized object is not a StorageObject.");
                        //

                        //tag with id and class
                        $object->id = $item_row[$bean->key()];
                        $object->className = get_class($bean);

                        $value = $object;
                    }
                }
            }
            else if ($this->transact_mode == InputProcessor::TRANSACT_DBROW) {
                $object = StorageObject::reconstruct($item_row, $name);
                $uid = $object->getUID();

                $object->id = $item_row[$bean->key()];
                $object->className = get_class($bean);

                $value = $object;
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
            debug("Key '$name' not found in the bean result row - trying values from source of DataInput");

            if (!$this->transact_bean) {
                debug("DataInput source is NULL");
                $values = NULL;
            }
            else {

                debug("Using transact bean: " . get_class($this->transact_bean));

                $source_fields = $this->transact_bean->fields();
                $source_key = $this->transact_bean->key();

                if (!in_array($item_key, $source_fields)) throw new Exception("Key '$item_key' was not found in the DataInput 'source' fields");

                debug("Processing DataInput 'source' values using key: '$item_key'");

                $source_values = array();
                $qry = $this->transact_bean->queryField($item_key, $editID);
                $qry->exec();

                while ($row = $qry->next()) {

                    debug("DataSourceLoadID: $source_key=>" . $row[$source_key]);

                    $source_values[] = $this->processRowData($row, $source_key);
                    // 		$source_values[$row[$source_key]] = $this->processRowData($field, $row, $source_key);

                }

                debug("Data source values loaded: ", $source_values);
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

}

?>