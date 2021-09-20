<?php
include_once("input/processors/InputProcessor.php");
include_once("storage/FileStorageObject.php");
include_once("storage/ImageStorageObject.php");

class SessionUploadInput extends InputProcessor
{



    //uids as keys -
    //loaded objects from the main transaction row
    protected $loaded_uids = array();

    //keeps map of storage_object UID to data_source primary key value
    protected $source_loaded_uids = array();

    public function __construct(DataInput $input)
    {
        if ($input instanceof ArrayDataInput) {
            parent::__construct($input);
        }
        else {
            throw new Exception("Expecting ArrayDataInput");
        }

    }



    //only one storage object can be loaded from the main transaction result row
    public function loadBeanData(int $editID, DBTableBean $bean, array &$item_row)
    {

        parent::loadBeanData($editID, $bean, $item_row);

        //now input->getValue() contains serialized data from target bean or single item from this transact row

        $values = $this->input->getValue();

        //no values loaded
        if (is_null($values)) return;

        //DataInput should be ArrayDataInput for this processor. InputProcessor::loadBeanData takes care to do this
        if (!is_array($values)) throw new Exception("Expecting array value");

        //incorrect max_slots usage
        if (count($values) > 1 && !$this->transact_bean) throw new Exception("Incorrect array size - can only load one StorageObject from the main result bean");

        foreach ($values as $idx => $value) {

            @$object = unserialize($value);
            if ($object) {
                $value = $object;
            }
            if ($value instanceof StorageObject) {

                $uid = $value->getUID();

                if ($this->transact_bean) {
                    $value->id = $this->target_loaded_keys[$idx];
                    $value->className = get_class($this->transact_bean);

                    $this->source_loaded_uids[$uid] = $value->id;
                }
                else {
                    $value->id = $item_row[$bean->key()];
                    $value->className = get_class($bean);

                    $this->loaded_uids = array();
                    $this->loaded_uids[$uid] = 1;
                }
                $values[$idx] = $value;

            }
            else {

                //De-serialized object is not instance of StorageObject");
                //do not throw here just unset
                unset($values[$idx]);
                debug("De-serialized object is not instance of StorageObject");

            }
        }

        $this->input->setValue($values);

    }

    public function loadPostData(array &$arr)
    {
        //
        //arr holds the posted UIDs
        //
        $name = $this->input->getName();

        debug("DataInput '{$name}' Type: " . get_class($this->input));

        $values = $this->input->getValue();

        $num_files = 0;

        $session_files = array();
        if (isset($_SESSION[UploadControlResponder::PARAM_CONTROL_NAME][$name])) {
            $session_files = $_SESSION[UploadControlResponder::PARAM_CONTROL_NAME][$name];
        }

        $posted_uids = array();
        if (isset($arr["uid_$name"])) {

            debug("Found posted UIDs for DataInput '$name'");
            if (is_array($arr["uid_$name"])) {
                $posted_uids = $arr["uid_$name"];
            }
            else {
                $posted_uids[] = $arr["uid_$name"];
            }
        }

        debug("Final UIDs posted:", $posted_uids);

        //remove from session files with non-posted uids
        foreach ($session_files as $uid => $file) {

            if (!in_array($uid, $posted_uids)) unset($session_files[$uid]);

        }
        //remove from field objects with non posted uids
        foreach ($values as $idx => $storage_object) {
            $uid = $storage_object->getUID();
            if (!in_array($uid, $posted_uids)) unset($values[$idx]);
        }

        //merge remaining session files
        foreach ($session_files as $uid => $file) {

            @$storage_object = unserialize($file);
            if ($storage_object instanceof StorageObject) {
                $values[] = $storage_object;
                debug("De-serialized UID: " . $storage_object->getUID() . " append to field values");

            }
            else {
                debug("[$uid] could not be de-serialized as StorageObject - removing from session array");
                unset($session_files[$uid]);
            }

        }

        //reorder
        $values = array_values($values);

        $this->input->setValue($values);

        debug("Final values including session files: ", $values);

    }

    public function afterCommit(BeanTransactor $transactor)
    {
        parent::afterCommit($transactor);

        $field_name = $this->input->getName();

        if (isset($_SESSION[UploadControlResponder::PARAM_CONTROL_NAME][$field_name])) {

            unset($_SESSION[UploadControlResponder::PARAM_CONTROL_NAME][$field_name]);
            debug("Cleared Session field['$field_name']");
        }
        if (isset($_SESSION["upload_control_removed"][$field_name])) {
            unset($_SESSION["upload_control_removed"][$field_name]);
            debug("Cleared Session Removed UIDs for field['$field_name']");
        }

        if ($this->transact_bean instanceof OrderedDataBean) {
            $this->transact_bean->rebuildReferentialOrdering($transactor->getBean()->key(), $transactor->getLastID());
        }
    }

    public function transactValue(BeanTransactor $transactor)
    {

        $name = $this->input->getName();
        debug("DataInput: '{$name}'");

        if ($this->transact_bean) {
            debug("DataInput: '{$name}' uses transact bean - values will be transacted in beforeCommit() ...");
            return;
        }

        $value = $this->input->getValue();

        if (is_array($value)) {

            if (count($value) > 1) {
                throw new Exception("Not possible to transact array of objects during to the main transaction row");
            }
            if (count($value) < 1) {
                debug("Array count is 0. Transacting NULL value to the main transaction row");
                $value = NULL;

                $transactor->appendValue($this->transact_column, $value);
            }
            else {
                $value = $value[0];
            }
        }

        if (is_null($value)) {

            return;
        }

        if (!($value instanceof StorageObject)) {
            throw new Exception("Value to transact is not instance of StorageObject");
        }

        $uid = $value->getUID();

        if (array_key_exists($uid, $this->loaded_uids)) {
            //Skip the value as this is edit and the object is not changed
            debug("StorageObject UID: $uid is the same UID as the bean loaded one.");
            //$transactor->removeValue($column);
        }
        else {
            //serialize
            debug("Serializing object of type: '" . get_class($value) . "'");
            $value = DBConnections::Get()->escape(serialize($value));
            $transactor->appendValue($this->transact_column, $value);
        }

    }

    public function beforeCommit(BeanTransactor $transactor, DBDriver $db, string $item_key)
    {

        if (!$this->transact_bean) {
            debug("transact_bean is null - nothing to do in beforeCommit");
            return;
        }

        $name = $this->input->getName();

        debug("UIDs loaded from bean '" . get_class($this->transact_bean) . "': ", $this->source_loaded_uids);

        $processed_ids = array();

        $foreign_transacted = 0;

        $values = $this->input->getValue();

        debug("Values count: " . count($values));

        foreach ($values as $idx => $value) {

            if (!($value instanceof StorageObject)) throw new Exception("Value at position $idx not instance of StorageObject");

            $uid = $value->getUID();

            debug("Processing UID: $uid");

            if (!array_key_exists($uid, $this->source_loaded_uids)) {
                debug("Found new UID: $uid for insert");

                $data = array();
                $data[$item_key] = $transactor->getLastID();

                $data[$name] = $db->escape(serialize($value));

                $refID = $this->transact_bean->insert($data, $db);
                if ($refID < 1) throw new Exception("Unable to insert into transact_bean. Error: " . $db->getError());
                $foreign_transacted++;

                $processed_ids[] = $refID;

                debug("StorageObject UID: $uid transacted to transact_bean. ID: " . $refID);
            }
            else {
                //skip transaction. same uid
                $processed_ids[] = $this->source_loaded_uids[$uid];
            }

        }

        //delete remaining values - transact_bean primary key values not found in processed_ids
        $this->transact_bean->deleteRef($item_key, $transactor->getLastID(), $db, $processed_ids);

        debug("Remaining transact_bean values removed");

    }
}

?>