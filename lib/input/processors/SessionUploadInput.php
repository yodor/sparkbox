<?php
include_once("input/processors/InputProcessor.php");
include_once("storage/FileStorageObject.php");
include_once("storage/ImageStorageObject.php");

class SessionUploadInput extends InputProcessor
{

    public $max_slots = 1;

    protected $loaded_uids = array();

    public function loadBeanData(int $editID, DBTableBean $bean, array &$item_row)
    {
        parent::loadBeanData($editID, $bean, $item_row);
        //now value contains array of this item row storage objects or referenced source data values
        //

        $field_name = $this->input->getName();

        $values = $this->input->getValue();
        $this->loaded_uids = array();

        //trying to load field that does not have corresponding value in table. reset the value to empty array
        if (is_null($values)) {
            $values = array();
        }

        foreach ($values as $idx => $storage_object) {

            //non required fields holding storage objects can load NULL values, remove them as they dont need presentation
            if (is_null($storage_object)) {
                unset($values[$idx]);
                continue;
            }

            $uid = $storage_object->getUID();
            $this->loaded_uids[$uid] = 1;

        }

        $values = array_values($values);
        $this->input->setValue($values);

        debug("Final value type: " . getType($values));
        debug("Final UIDs Dump: ", $values);
    }

    public function loadPostData(array &$arr)
    {

        //
        //arr holds the posted UIDs
        //

        debug("DataInput class: " . get_class($this->input));

        $field_name = $this->input->getName();

        $values = $this->input->getValue();

        $num_files = 0;

        $session_files = array();
        if (isset($_SESSION[UploadControlAjaxHandler::PARAM_CONTROL_NAME][$field_name])) {
            $session_files = $_SESSION[UploadControlAjaxHandler::PARAM_CONTROL_NAME][$field_name];
        }

        $posted_uids = array();
        if (isset($arr["uid_$field_name"])) {

            debug("Found posted UIDs for field['$field_name']");
            if (is_array($arr["uid_$field_name"])) {
                $posted_uids = $arr["uid_$field_name"];
            }
            else {
                $posted_uids[] = $arr["uid_$field_name"];
            }
        }

        debug("Final UIDs posted:", $posted_uids);

        //remove from session files with non-posted uids
        foreach ($session_files as $uid => $file) {

            if (!in_array($uid, $posted_uids)) unset($session_files[$uid]);

        }
        //remove from field values objects with non posted uids
        foreach ($values as $idx => $storage_object) {
            $uid = $storage_object->getUID();
            if (!in_array($uid, $posted_uids)) unset($values[$idx]);
        }

        //merge remaining session files
        foreach ($session_files as $uid => $file) {

            @$storage_object = unserialize($file);
            if ($storage_object instanceof StorageObject) {
                $values[] = $storage_object;
                debug("Deserialized UID: " . $storage_object->getUID() . " append to field values");

            }
            else {
                debug("[$uid] could not be deserialized as StorageObject - removing from session array");
                unset($session_files[$uid]);
            }

        }

        //reorder
        $values = array_values($values);

        $this->input->setValue($values);

        debug("Final field values including session fiels:", $values);

    }

    public function afterCommit(BeanTransactor $transactor)
    {
        $field_name = $this->input->getName();

        if (isset($_SESSION[UploadControlAjaxHandler::PARAM_CONTROL_NAME][$field_name])) {

            unset($_SESSION[UploadControlAjaxHandler::PARAM_CONTROL_NAME][$field_name]);
            debug("Cleared Session field['$field_name']");
        }
        if (isset($_SESSION["upload_control_removed"][$field_name])) {
            unset($_SESSION["upload_control_removed"][$field_name]);
            debug("Cleared Session Removed UIDs for field['$field_name']");
        }

    }

    public function transactValue(BeanTransactor $transactor)
    {

        $values = $this->input->getValue();
        $field_name = $this->input->getName();

        //transact only UIDs found inside the session array i.e. the new ones
        debug("DataInput ['$field_name'] Value Type: " . gettype($values) . " #" . count($values) . " values to transact");

        if ($this->transact_bean) {

            debug("Using transact bean: '" . get_class($this->transact_bean) . "' will commit values in beforeCommit() ...");
            return;
        }

        if ($this->transact_mode == InputProcessor::TRANSACT_DBROW) {

            debug("Transact Mode: TRANSACT_DBROW");

            if (count($values) > 1) {
                throw new Exception("Could not transact multiple objects to the main transaction using TRANSACT_DBROW mode.");
            }
            if (count($values) < 1) {
                throw new Exception("Could not transact empty object to the main transaction using TRANSACT_DBROW mode. (effective result will be delete of the main transaction row)");
            }
            //expecting single object
            foreach ($values as $idx => $storage_object) {
                $uid = $storage_object->getUID();

                //this object is the same as the one that was loaded
                if (array_key_exists($uid, $this->loaded_uids)) {
                    debug("Object with UID: $uid as the same UID as the bean loaded one. Not transacting this object.");
                }
                else {
                    debug("Transacting StorageObject UID: $uid merged with the main transaction row ");
                    $dbrow = array();
                    $storage_object->setDataKey($field_name);
                    $storage_object->deconstruct($dbrow);
                    foreach ($dbrow as $key => $field_value) {
                        $transactor->appendValue($key, $field_value);
                    }
                    debug("Deconstructed UID: $uid as fields in the main transaction row");

                }
                break;
            }

        }
        else if ($this->transact_mode == InputProcessor::TRANSACT_OBJECT) {
            debug("Transact Mode: MODE_OBJECT");

            if (count($values) > 1) {
                throw new Exception("Could not transact multiple objects to the main transaction using TRANSACT_OBJECT mode.");
            }

            if (count($values) < 1) {
                debug("Field does not contain values. Transacting NULL value to the main transaction row");
                $value = NULL;
                $transactor->appendValue($field_name, $value);
            }

            //transact the first value if it is not the same as the loaded one
            foreach ($values as $idx => $storage_object) {
                $uid = $storage_object->getUID();

                if (array_key_exists($uid, $this->loaded_uids)) {
                    debug("StorageObject UID: $uid is the same UID as the bean loaded one. Not transacting this object to the main transaction row.");
                }
                else {
                    debug("Transacting StorageObject UID: $uid serialized to the main transaction row");
                    $value = DBConnections::Get()->escape(serialize($storage_object));
                    $transactor->appendValue($field_name, $value);
                }
                break;
            }

        }
        else {
            throw new Exception("Could not transact this field using mode TRANSACT_VALUE");
        }

        debug("field['$field_name'] finished values");

    }
}

?>