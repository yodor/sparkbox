<?php
include_once("lib/input/processors/IBeanPostProcessor.php");
include_once("lib/input/transactors/IDBFieldTransactor.php");

class BeanPostProcessor implements IBeanPostProcessor, IDBFieldTransactor
{

    //keeps map of storage_object UID to data_source primary key value
    protected $source_loaded_uids = array();

    protected $source_loaded_keys = array();

    //foreign keys are posted in the order or values, if true will process them. used in direct mapping between fields.
    public $process_datasource_foreign_keys = false;

    public $transact_empty_string_as_null = false;

    //field source copy fields
    public $bean_copy_fields = array();

    public $renderer_source_copy_fields = array();

    public function __construct()
    {

    }

    public function beforeCommit(DataInput $input, DBTransactor $transactor, DBDriver $db, $item_key)
    {
        $data_source = $input->getSource();
        if (!($data_source instanceof DBTableBean)) {
            debug("Data source is null or not from expected class DBTableBean nothing to do in beforeCommit");
            return;
        }
        if ($data_source->getTableName() == $transactor->getBean()->getTableName()) {
            throw new Exception("Field transact error for field: '" . $input->getName() . "'. Data source bean and main transaction bean are the same - '" . get_class($data_source) . "'");
        }

        $source_key = $data_source->key();
        $lastID = $transactor->getLastID();

        $field_name = $input->getName();

        debug("Using data Source: " . get_class($data_source) . " | Field: $field_name | lastID: $lastID | Source PrimaryKey: $source_key | Field Values count: " . count($input->getValue()));


        $foreign_transacted = 0;

        if (count($input->getValue()) < 1) {

            debug("0 values to transact to data source. Clearing all rows of the data source: " . get_class($data_source));
            $data_source->deleteRef($item_key, $transactor->getLastID(), $db);

            return;
        }

        if ($input->transact_mode == DataInput::TRANSACT_VALUE) {
            debug("Transact Mode: TRANSACT_VALUE");
            // 	    $data_source->deleteRef($item_key, $lastID, $db);
            debug("Merging updated values ...");

            //TODO:try to update data source found in source_loaded_values. Delete removed values. Keep order of loaded
            //TODO: !!! merging is not really possible if there are unique constraints on the primary key and the foreign key as it tries to update before deleting the old key

            $processed_ids = array();

            debug("field values count: " . count($input->getValue()));
            // 	    debug("Post Values: ", $_POST);

            foreach ($input->getValue() as $idx => $value) {

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
                    $bean_fields = $transactor->getTransactionValues();
                    foreach ($bean_fields as $key => $val) {
                        if (in_array($key, $this->bean_copy_fields)) {
                            $dbrow[$key] = $val;
                        }
                    }
                }

                $sourceID = array_shift($this->source_loaded_keys);
                if ($sourceID > 0) {
                    debug("DataSourceID: " . $sourceID);

                    if (!$data_source->update($sourceID, $dbrow, $db)) throw new Exception("Unable to update  data source bean. Error: " . $db->getError());
                    $processed_ids[] = $sourceID;
                }
                else {

                    $refID = $data_source->insert($dbrow, $db);
                    if ($refID < 1) throw new Exception("Unable to insert into data source bean. Error: " . $db->getError());
                    $processed_ids[] = $refID;
                }
                $foreign_transacted++;

            }

            //TODO:duplicate keys might get triggered
            $data_source->deleteRef($item_key, $transactor->getLastID(), $db, $processed_ids);


        }
        else {

            debug("Processing for non TRANSACT_VALUE modes ... ");

            debug("Current source loaded UIDs dump: ", $this->source_loaded_uids);

            $processed_ids = array();

            foreach ($input->getValue() as $idx => $value) {

                if (!($value instanceof StorageObject)) throw new Exception("Transact mode TRANSACT_DBROW can transact only values of type  StorageObject");

                $uid = $value->getUID();

                debug("Processing UID: $uid");


                if (!array_key_exists($uid, $this->source_loaded_uids)) {
                    debug("StorageObject UID: $uid not found in the loaded keys array. Will commit insert operation to data source ...");

                    //new value need insert
                    $dbrow = array();
                    $dbrow[$item_key] = $transactor->getLastID();

                    if ($input->transact_mode == DataInput::TRANSACT_DBROW) {
                        debug("Transact Mode: TRANSACT_DBROW");
                        $value->setDataKey($field_name);
                        $value->deconstruct($dbrow);

                        debug("StorageObject UID: $uid deconstructed as fields in the data source row ...");

                    }
                    else if ($input->transact_mode == DataInput::TRANSACT_OBJECT) {
                        debug("Transact Mode: TRANSACT_OBJECT");
                        $dbrow[$field_name] = $db->escapeString(serialize($value));
                        debug("StorageObject UID: $uid stored as serialized value in the data source row ...");
                    }
                    else {
                        throw new Exception("Unknown transact mode: " . $input->transact_mode);
                    }

                    $refID = $data_source->insert($dbrow, $db);
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
            $data_source->deleteRef($item_key, $transactor->getLastID(), $db, $processed_ids);

            debug("Remaining data source keys removed");

        }

        debug("Total $foreign_transacted rows transacted to data source: " . get_class($data_source));

    }

    public function afterCommit(DataInput $input, DBTransactor $transactor)
    {
        //
    }

    //

    public function transactValue(DataInput $input, DBTransactor $transactor)
    {
        switch ($input->transact_mode) {
            case DataInput::TRANSACT_VALUE:
                $value = $input->getValue();

                if (!is_array($value)) {
                    if (strlen($value) == 0 && $this->transact_empty_string_as_null) {
                        $value = NULL;
                    }
                }

                $transactor->appendValue($input->getName(), $value);

                //transacts additional values from renderer data source
                //matching by 'this' field name and its value posted. do not copy on empty 'value'
                if (is_array($this->renderer_source_copy_fields) && count($this->renderer_source_copy_fields) > 0 && $value) {
                    debug("value[" . $input->getName() . "] Renderer copy fields requested ... ");
                    $renderer = $input->getRenderer();
                    $renderer_source = $renderer->getIterator();
                    if (!$renderer_source instanceof DBTableBean) throw new Exception("Renderer copy fields requested without DBTableBean renderer source");
                    $source_fields = $renderer_source->fields();

                    debug("value[" . $input->getName() . "] Renderer source: " . get_class($renderer_source) . " | List key: [{$renderer->list_key}] | List label: [{$renderer->list_label}]");

                    if (!in_array($renderer->list_key, $source_fields)) throw new Exception("List Key '{$renderer->list_key}' not found in renderer source fields");
                    if (!in_array($renderer->list_label, $source_fields)) throw new Exception("List Label '{$renderer->list_label}' not found in renderer source fields");

                    $row = $renderer_source->getByRef($renderer->list_key, $value);
                    if (!$row) throw new Exception("Unable to query renderer source data for field: " . $input->getName());
                    foreach ($this->renderer_source_copy_fields as $idx => $field_name) {
                        debug("value[" . $input->getName() . "] | Doing copy for source field [$field_name]");
                        if (isset($row[$field_name])) {
                            $transactor->appendValue($field_name, $row[$field_name]);
                            debug("value[" . $input->getName() . "] | source field [$field_name] value transacted to main transaction");
                        }
                        else {
                            throw new Exception("Requested copy field [$field_name] not found in data row");
                        }
                    }
                }

                break;
            case DataInput::TRANSACT_DBROW:
                throw new Exception("Unsupported TRANSACT_DBROW for input field['" . $input->getName() . "']");
                break;
            case DataInput::TRANSACT_OBJECT:
                throw new Exception("Unsupported TRANSACT_OBJECT for input field['" . $input->getName() . "']");
                break;
            default:
                throw new Exception("Unsupported transaction mode for input field['" . $input->getName() . "']");
        }

    }

    protected function processRowData(DataInput $field, &$item_row, $source_key)
    {
        $value = NULL;
        $field_name = $field->getName();

        if ($field->transact_mode == DataInput::TRANSACT_VALUE) {

            $value = $item_row[$field_name];
            $this->source_loaded_keys[] = $item_row[$source_key];
            debug("SourceLoaded value for datasource keyID: " . $item_row[$source_key]);

        }
        if ($field->transact_mode == DataInput::TRANSACT_DBROW) {

            $value = StorageObject::reconstruct($item_row, $field_name);
            $uid = $value->getUID();

            $source_key_value = $item_row[$source_key];

            $this->source_loaded_uids[$uid] = $source_key_value;

            debug("Source Load UID: Reconstructed StorageObject UID: $uid From data source $source_key='$source_key_value'");

        }
        else if ($field->transact_mode == DataInput::TRANSACT_OBJECT) {

            $value = $item_row[$field_name];

            $storage_object = @unserialize($value);
            if ($storage_object !== false) {
                if (!($storage_object instanceof StorageObject)) throw new Exception("Deserialized object is not a StorageObject.");

                //tag with id and class
                $storage_object->id = $item_row[$source_key];
                $storage_object->className = get_class($field->getSource());

                $value = $storage_object;

                $uid = $value->getUID();

                $source_key_value = $item_row[$source_key];

                $this->source_loaded_uids[$uid] = $source_key_value;

                debug("Source Load UID:  Deserialized StorageObject UID: $uid From data source $source_key='$source_key_value'");

            }
            else {
                if (!is_null($value)) throw new Exception("Expected serialized contents in '$field_name' of data source: " . get_class($field->getSource()));
            }
        }
        return $value;
    }


    //called from InputFormView to load the field data from bean fields
    public function loadBeanData(int $editID, DBTableBean $bean, DataInput $input, array &$item_row)
    {
        $name = $input->getName();
        $item_key = $bean->key();
        debug("input [$name] for bean: " . get_class($bean) . " | item_key: $item_key");

        $values = array();

        if (array_key_exists($name, $item_row)) {

            debug("'$name' found in the item_row - loading value");

            $value = $item_row[$name];

            if ($input->transact_mode == DataInput::TRANSACT_OBJECT) {
                //non required fields holdings storage objects can load NULL values, remove them as they dont need presentation
                if (!is_null($value)) {
                    $object = @unserialize($value);
                    if ($object !== false) {
                        if (!($object instanceof StorageObject)) throw new Exception("Deserialized object is not a StorageObject.");
                        //

                        //tag with id and class
                        $object->id = $item_row[$bean->key()];
                        $object->className = get_class($bean);

                        $value = $object;
                    }
                }
            }
            else if ($input->transact_mode == DataInput::TRANSACT_DBROW) {
                $object = StorageObject::reconstruct($item_row, $name);
                $uid = $object->getUID();

                $object->id = $item_row[$bean->key()];
                $object->className = get_class($bean);

                $value = $object;
            }

            //TODO: InputField::TRANSACT_OBJECT can be array ?
            if ($input instanceof ArrayDataInput) {
                $values = array($value);
            }
            else {
                $values = $value;
            }

        }
        else {

            //process data source values
            debug("field '$name' not found in item_row - trying field source values");

            $data_source = $input->getSource();

            if (is_null($data_source)) {
                debug("'$name' no data source is set for this field");
                $values = NULL;
            }
            else {

                debug("field: '$name' | Using Data Source: " . get_class($data_source));

                if (!($data_source instanceof DBTableBean)) throw new Exception("Received data source: '" . get_class($data_source) . "' but 'DBTableBean' expected.");

                $source_fields = $data_source->fields();
                $source_key = $data_source->key();

                if (!in_array($item_key, $source_fields)) throw new Exception("ItemKey: $item_key not exist in data source fileds");

                debug("processing data_source values using access field: '$item_key'");

                $source_values = array();
                $qry = $data_source->queryField($item_key, $editID);
                $qry->exec();

                while ($row = $qry->next()) {

                    debug("DataSourceLoadID: $source_key=>" . $row[$source_key]);

                    $source_values[] = $this->processRowData($input, $row, $source_key);
                    // 		$source_values[$row[$source_key]] = $this->processRowData($field, $row, $source_key);

                }

                debug("Data source values loaded: ", $source_values);
                $values = $source_values;

            }

        }//array_key_exists

        //       debug("SimpleInputProcessor::loadBeanData: | Final field values: ",$values);
        $input->setValue($values);

    }

    //called from post
    public function loadPostData(DataInput $input, array &$arr)
    {

        $name = $input->getName();

        //sanitize non-compound fields
        if (array_key_exists($name, $arr)) {

            $value = $arr[$name];

            $value = sanitizeInput($value, $input->accepted_tags);

            $input->setValue($value);

        }
        else {
            $input->clear();
        }

    }

}

?>