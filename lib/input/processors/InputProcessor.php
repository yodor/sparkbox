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

    protected $max_transact_bean_items = -1;
    /**
     * DataInput value will not be transacted if this flag is true
     * @var bool
     */
    public $skip_transaction = FALSE;

    /**
     * Search the $_REQUEST data for items with keys =  fk_$name where name is the DataInput name
     * and use them as values during beforeCommit
     *
     * @var bool
     */
    public $process_datasource_foreign_keys = FALSE;

    /**
     * Transact NULL instead of "" if strlen of DataInput value is 0
     * @var bool
     */
    public $transact_empty_string_as_null = FALSE;

    /**
     * Copy data from the main transaction row to the transact_bean - matching values having keys present in this array_
     * @var array
     */
    public $bean_copy_fields = array();

    /**
     * Copy data from the renderer iterator of the DataInput.
     * Will query the iterator with fields set to the contents of this array and match by the DataInput name and value
     * @var array
     */
    public $renderer_source_copy_fields = array();

    /**
     * Override the default accepted tags during sanitizeInput in loadPostData().
     * Default is = DefaultAcceptedTags()
     * @var string
     */
    public $accepted_tags;

    /**
     * The primary key values for each result loaded from the 'transact_bean' during load bean data
     * @var array
     */
    protected $target_loaded_keys = array();

    /**
     * Override transact column name - default is = DataInput.getName() set in the CTOR
     * @var string
     */
    protected $transact_column;

    /**
     *
     * @var DataInput
     */
    protected $input;

    /**
     * Transact the DataInput values to this bean instead of the main transaction row
     * @var DBTableBean
     */
    protected $transact_bean;

    public $transact_bean_skip_empty_values = FALSE;

    public function __construct(DataInput $input)
    {
        $this->input = $input;
        $this->input->setProcessor($this);

        $this->accepted_tags = DefaultAcceptedTags();

        $this->transact_column = $input->getName();
    }

    public function getTransactBean(): ?DBTableBean
    {
        return $this->transact_bean;
    }

    /**
     * Set the 'transact bean' to be used for this DataInput
     * Loading of values is done in loadTargetBean and storing is done in beforeCommit
     * Data from this field will be stored into '$bean' DBTableBean instead of the main
     * transaction bean.
     * Multiple values are loaded using the default SQLSelect returned from by the select() method of '$bean'
     * Ordering might be adjusted by modifying the select of '$bean' before passing it here
     * ie. $bean->select()->order_by = " position ASC "
     * @param DBTableBean $bean
     * @param int $max_items
     */
    public function setTransactBean(DBTableBean $bean, int $max_items=-1)
    {
        $this->transact_bean = $bean;
        $this->max_transact_bean_items = $max_items;
    }

    public function getTransactBeanItemLimit() : int
    {
        return $this->max_transact_bean_items;
    }

    public function setTransactBeanItemLimit(int $max_items)
    {
        $this->max_transact_bean_items = $max_items;
    }

    public function setTargetColumn(string $name)
    {
        $this->transact_column = $name;
    }

    /**
     * DBTransactor calls this method just before commit() on the main transaction
     * If DataInput has assigned 'Transact' bean each value is inserted/updated to this bean
     *
     * @param BeanTransactor $transactor
     * @param DBDriver $db
     * @param string $item_key Main transaction bean primary key
     * @throws Exception
     */
    public function beforeCommit(BeanTransactor $transactor, DBDriver $db, string $item_key)
    {

        if (!$this->transact_bean) {
            debug("transact_bean is null - nothing to do in beforeCommit");
            return;
        }

        if (strcmp($this->transact_bean->getTableName(), $transactor->getBean()->getTableName()) == 0) {
            throw new Exception("Transact error: DataInput '" . $this->input->getName() . "' have transact bean equal to the main transaction bean - '" . get_class($this->transact_bean) . "'");
        }

        $source_key = $this->transact_bean->key();
        $lastID = $transactor->getLastID();

        $name = $this->input->getName();

        $values = $this->input->getValue();
        if (!is_array($values)) throw new Exception("DataInput value is not array");

        debug("DataInput '{$name}' transact bean: " . get_class($this->transact_bean) . " lastID: $lastID | Transact bean primary key: $source_key | DataInput values count: " . count($values));

        $foreign_transacted = 0;

        if (count($values) < 1) {

            debug("Values count is zero. Clearing all referencing rows of the transact_bean: " . get_class($this->transact_bean));
            $this->transact_bean->deleteRef($item_key, $transactor->getLastID(), $db);
            return;
        }

        debug("Merging updated values ...");

        //TODO:try to update data source found in source_loaded_values. Delete removed values. Keep order of loaded
        //TODO: !!! merging is not really possible if there are unique constraints on the primary key and the foreign key as it tries to update before deleting the old key

        $processed_ids = array();

        debug("Values count: " . count($values));
        // 	    debug("Post Values: ", $_POST);


        //TODO: fix mismatch of posted count to datasource loaded count
        //possible fix: delete all values and insert again only if mismatch of count

        if (count($this->target_loaded_keys)!=count($values)) {
            //delete all and insert
            debug("Values count does not match posted values count - deleting all values and inserting posted ones");
            $this->transact_bean->deleteRef($item_key, $transactor->getLastID(), $db);
            $this->target_loaded_keys = array();
        }

        foreach ($values as $idx => $value) {

            $data = array();
            $skip_insert = false;

            //referencing key is the primary key of the bean from main transaction
            $data[$item_key] = $transactor->getLastID();

            if (is_array($value)) throw new Exception("Could not transact Array to target_bean");
            if (is_object($value)) throw new Exception("Could not transact Object to target_bean");

            //check flag for empty value transacting
            if ($this->transact_bean_skip_empty_values && !$value) {
                //TODO: check
                if (count($this->target_loaded_keys)>0) array_shift($this->target_loaded_keys);
                continue;
            }

            $data[$name] = $value;

            //process posted foreign keys and assign them
            if ($this->process_datasource_foreign_keys) {
                if (isset($_REQUEST["fk_$name"][$idx])) {
                    $fks = $_REQUEST["fk_$name"][$idx];
                    $fk_pairs = explode("|", $fks);
                    foreach ($fk_pairs as $fk_idx => $fk_pair) {
                        list($fk_name, $fk_value) = explode(":", $fk_pair);
                        $data[$fk_name] = $fk_value;
                    }
                }
            }

            //copy values from the main transaction to the transact_bean
            if (is_array($this->bean_copy_fields) && count($this->bean_copy_fields) > 0) {
                $bean_fields = $transactor->getValues();
                foreach ($bean_fields as $key => $val) {
                    if (in_array($key, $this->bean_copy_fields)) {
                        $data[$key] = $val;
                    }
                }
            }

            //try update on 1:1 load vs post values
            if (count($this->target_loaded_keys)>0) {
                $sourceID = array_shift($this->target_loaded_keys);
                if ($sourceID > 0) {
                    debug("DataSourceID: " . $sourceID);

                    $this->transact_bean->update($sourceID, $data, $db);
                    //Do not throw here - might return 0 updated rows
                    // new Exception("Unable to update  data source bean. Error: " . $db->getError());
                    $processed_ids[] = $sourceID;
                    $skip_insert = true;
                }

            }

            if (!$skip_insert) {
                $refID = $this->transact_bean->insert($data, $db);
                if ($refID < 1) throw new Exception("Unable to insert into data source bean. Error: " . $db->getError());
                $processed_ids[] = $refID;
            }

            $foreign_transacted++;

        }

        //TODO:duplicate keys might get triggered
        debug("Deleting remaining transact_bean values");
        $this->transact_bean->deleteRef($item_key, $transactor->getLastID(), $db, $processed_ids);

        debug("Total $foreign_transacted rows transacted to transact_bean: " . get_class($this->transact_bean));

    }

    public function afterCommit(BeanTransactor $transactor)
    {
        //
    }

    //

    /**
     * @param BeanTransactor $transactor
     * @return mixed|null
     * @throws Exception
     */
    public function transactValue(BeanTransactor $transactor)
    {

        $name = $this->input->getName();
        debug("DataInput: '{$name}'");

        if ($this->transact_bean) {
            debug("DataInput: '{$name}' uses transact bean - values will be transacted in beforeCommit() ...");
            return;
        }

        $value = $this->input->getValue();

        if (is_array($value)) throw new Exception("Unable to transact array as value");
        if (is_object($value)) throw new Exception("Unable to transact object as value");

        if (strlen($value) == 0 && $this->transact_empty_string_as_null) {
            $value = NULL;
        }

        $transactor->appendValue($this->transact_column, $value);

        //transacts additional values from renderer data source
        //matching by 'this' field name and its value posted. do not copy on empty 'value'
        if (is_array($this->renderer_source_copy_fields) && count($this->renderer_source_copy_fields) > 0 && $value) {
            debug("renderer_source_copy_fields ... using renderer iterator to query additional values");
            $iterator = $this->input->getRenderer()->getIterator();
            if (!($iterator instanceof SQLQuery)) throw new Exception("Unsupported iterator");
            $iterator->select->fields()->reset();
            $iterator->select->fields()->set(...$this->renderer_source_copy_fields);
            $iterator->select->where()->add($name, $value);
            $iterator->select->limit = 1;
            if ($iterator->exec() && $data = $iterator->next()) {
                foreach ($this->renderer_source_copy_fields as $idx => $key) {
                    $transactor->appendValue($key, $data[$key]);
                }
            }
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

        $value = NULL;

        if ($this->transact_bean) {
            debug("DataInput '$name' uses 'transact_bean' ");
            //load all from target_bean where '$bean->key()'='$editID'
            $value = $this->loadTargetBean($bean->key(), $editID);
        }
        else {
            if (!array_key_exists($name, $item_row)) {
                debug("No values to load for this DataInput - key '$name' does not exist in the result data row");
                return;
            }
            $value = $item_row[$name];
        }

        if ($value !== NULL) {
            if ($this->input instanceof ArrayDataInput && !is_array($value)) {
                $value = array($value);
            }

            $this->input->setValue($value);
        }
    }

    //query 'target bean' by using the main transaction bean primary key as referential access key with value $editID
    protected function loadTargetBean(string $column, int $value): array
    {
        $name = $this->input->getName();

        //process data source values
        debug("Loading values from transact bean: " . get_class($this->transact_bean) . " - primary key: " . $this->transact_bean->key());

        $source_key = $this->transact_bean->key();

        //referential key not found
        if (!in_array($column, $this->transact_bean->columnNames())) throw new Exception("Referential column '$column' not found in the transact bean columns");

        debug("Querying transact bean values by $column = '$value' and setting value using key '$name' ");

        $values = array();

        $qry = $this->transact_bean->query($this->transact_bean->key(), $name);
        $qry->select->where()->add($column, $value);
        $num = $qry->exec();
        debug("Using SQL: ".$qry->select->getSQL());

        debug("Transact bean results: ".$num);
        while ($target_data = $qry->next()) {
            $values[] = $this->loadTargetBeanData($target_data);
        }

        debug("Transact bean values loaded #".count($values));
        foreach ($values as $idx=>$item) {
            @$obj = unserialize($item);

            if ($obj === FALSE) {
                $type = gettype($item);
                debug("Item[$idx] => Type: $type | '$item'");
            }
            else {
                $type = get_class($obj);
                if ($obj instanceof StorageObject) {
                    debug("Item[$idx] => Type: $type | UID: ".$obj->getUID());
                }
                else {
                    debug("Item[$idx] => Type: $type");
                }

            }
        }

        return $values;
    }

    protected function loadTargetBeanData(array &$target_data): ?string
    {
        $value = NULL;
        $name = $this->input->getName();
        $source_key = $this->transact_bean->key();

        $value = $target_data[$name];
        $this->target_loaded_keys[] = $target_data[$source_key];

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