<?php
include_once("input/processors/InputProcessor.php");
include_once("storage/FileStorageObject.php");
include_once("storage/ImageStorageObject.php");
include_once("utils/SessionData.php");

class SessionUploadInput extends InputProcessor
{

    //uids as keys -
    //loaded objects from the main transaction row
    protected array $loaded_uids = array();

    //keeps map of storage_object UID to data_source primary key value
    protected array $source_loaded_uids = array();

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
    public function loadBeanData(int $editID, DBTableBean $bean, array $data) : void
    {

        parent::loadBeanData($editID, $bean, $data);

        //now input->getValue() contains serialized data from target bean or single item from this transact row

        $values = $this->input->getValue();

        //no values loaded
        if (is_null($values)) return;

        //DataInput should be ArrayDataInput for this processor. InputProcessor::loadBeanData takes care to do this
        if (!is_array($values)) throw new Exception("Expecting array value");

        //incorrect max_slots usage
        if (count($values) > 1 && !$this->transact_bean) throw new Exception("Incorrect array size - can only load one StorageObject from the main result bean");

        //
        $position = -1;
        foreach ($values as $id => $value) {
            $position++;

            @$object = unserialize($value);
            if ($object) {
                $value = $object;
            }
            if ($value instanceof StorageObject) {

                $uid = $value->UID();

                if ($this->transact_bean) {
                    $this->source_loaded_uids[$uid] = $id; //id of
                }
                else {
                    $this->loaded_uids = array();
                    $this->loaded_uids[$uid] = 1;
                }
                //update the value
                $values[$id] = $value;

            }
            else {

                //De-serialized object is not instance of StorageObject");
                //do not throw here just unset
                unset($values[$id]);
                debug("Cleaning up non StorageObject: #$position - ID($id) - Value($value)");

            }
        }

        $this->input->setValue($values);

    }

    public function loadPostData(array $data) : void
    {
        //
        //posted $data holds the UIDs that have been uploaded previously using ajax/json
        //
        $name = $this->input->getName();

        debug("DataInput '$name' Type: " . get_class($this->input));

        $values = $this->input->getValue();

        $posted_uids = array();
        if (isset($data["uid_$name"])) {
            debug("Found posted UIDs for DataInput '$name'");
            if (is_array($data["uid_$name"])) {
                $posted_uids = $data["uid_$name"];
            }
            else {
                $posted_uids[] = $data["uid_$name"];
            }
        }

        //[0] => 1725976901.6922.1567366113
        debug("UIDs posted:", $posted_uids);

        $session_data = new SessionData(SessionData::Prefix($name, SessionData::UPLOAD_CONTROL));

        $stored_keys = $session_data->keys();
        debug("Session stored UIDs: ",$stored_keys);

        //remove keys that are not inside posted_uids
        foreach ($stored_keys as $uid) {
            if (!in_array($uid, $posted_uids)) $session_data->remove($uid);
        }

        if (is_array($values)) {
            //remove from field objects with non posted uids
            foreach ($values as $idx => $storage_object) {
                $uid = $storage_object->uid();
                if (!in_array($uid, $posted_uids)) unset($values[$idx]);
            }
        }
        else {
            //single input for same bean row
            $values = array();
        }

        $stored_keys = $session_data->keys();
        //merge remaining session files
        foreach ($stored_keys as $uid) {

            $storage_object = $session_data->get($uid);

            if ($storage_object instanceof StorageObject) {
                $values[] = $storage_object;
                debug("Appending StorageObject UID: " . $storage_object->UID() . " to field values");

            } else {
                debug("[$uid] is not StorageObject - removing from session array");
                $session_data->remove($uid);
            }

        }

        //reorder
        $values = array_values($values);

        $this->input->setValue($values);

        debug("Final values including session files: ", $values);

    }

    public function afterCommit(BeanTransactor $transactor) : void
    {
        parent::afterCommit($transactor);

        $field_name = $this->input->getName();

        debug("Clearing session data for field['$field_name']");
        $session_data = new SessionData(SessionData::Prefix($field_name, SessionData::UPLOAD_CONTROL));
        $session_data->destroy();
    }

    public function transactValue(BeanTransactor $transactor) : void
    {

        $name = $this->input->getName();
        debug("DataInput: '$name'");

        if ($this->transact_bean) {
            debug("DataInput: '$name' uses transact bean - values will be transacted in beforeCommit() ...");
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

        $uid = $value->UID();

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

    public function beforeCommit(BeanTransactor $transactor, DBDriver $db, string $item_key) : void
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

        //order position for OrderedDataBean
        $position = 0;

        foreach ($values as $idx => $value) {

            $position++;

            if (!($value instanceof StorageObject)) throw new Exception("Value at position $idx not instance of StorageObject");

            $uid = $value->UID();

            debug("Processing UID: $uid");

            if (!array_key_exists($uid, $this->source_loaded_uids)) {
                debug("Found new UID: $uid for insert");

                $data = array();
                $data[$item_key] = $transactor->getLastID();

                $data[$name] = $db->escape(serialize($value));

                $data["position"] = $position;

                $refID = $this->transact_bean->insert($data, $db);
                if ($refID < 1) throw new Exception("Unable to insert into transact_bean. Error: " . $db->getError());

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
