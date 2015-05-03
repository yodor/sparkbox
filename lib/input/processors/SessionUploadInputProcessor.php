<?php
include_once("lib/input/processors/BeanPostProcessor.php");
include_once("lib/storage/FileStorageObject.php");
include_once("lib/storage/ImageStorageObject.php");


class SessionUploadInputProcessor extends BeanPostProcessor
{

  
  public $max_slots = 1;
  
  protected $loaded_uids = array();
  
  
  public function loadBeanData($editID, DBTableBean $bean, InputField $field,  array $item_row)
  {
      parent::loadBeanData($editID, $bean, $field, $item_row);
      //now value contains array of this item row storage objects or referented source data values
      //
      debug("SessionUploadInputProcessor::loadBeanData: ");
      $field_name = $field->getName();

      $values = $field->getValue();
      $this->loaded_uids = array();
      
      //trying to load field that does not have corresponding value in table. reset the value to empty array
      if (is_null($values)) {
		  $values = array();
      }
      
      foreach ($values as $idx=>$storage_object)
      {

		//non required fields holdings storage objects can load NULL values, remove them as they dont need presentation
		if (is_null($storage_object)) {
			unset($values[$idx]);
			continue;
		}
		
		$uid = $storage_object->getUID();
		$this->loaded_uids[$uid] = 1;
	  
		//TODO: clickable link for imageuploadfield
// 		if (isset($this->source_loaded_uids[$uid])) {
// 		  $storage_object->itemID = $this->source_loaded_uids[$uid];
// 		  $storage_object->itemClass = get_class($field->getSource());
// 		}
		
		
      }
      
      $values = array_values($values);
      $field->setValue($values);
      
      debug("SessionUploadInputProcessor::loadBeanData | Final value type: ".getType($values));
      debugArray("SessionUploadInputProcessor::loadBeanData | Final UIDs Dump: ", $values);
  }

  

  public function loadPostData(InputField $field, array $arr)
  {

      //
      //arr holds the posted UIDs 
      //
      
      debug("-");

      debug("SessionUploadInputProcessor::loadPostData field class: ".get_class($field));
      $field_name = $field->getName();

      $values = $field->getValue();
      
      $num_files = 0;

      $session_files = array();
      if (isset($_SESSION["upload_control"][$field_name])) {
	$session_files = $_SESSION["upload_control"][$field_name];
      }
      
      $posted_uids = array();
      if (isset($arr["uid_$field_name"])) {
      
	debug("SessionUploadInputProcessor::loadPostData: Found posted UIDs for field['$field_name']");
	if (is_array($arr["uid_$field_name"])) {
	  $posted_uids = $arr["uid_$field_name"];
	}
	else {
	  $posted_uids[] = $arr["uid_$field_name"];
	}
      }

      debugArray("SessionUploadInputProcessor::loadPostData: Final UIDs posted:", $posted_uids);

      //remove from session files with non-posted uids
      foreach($session_files as $uid=>$file) {
	
	  if (!in_array($uid, $posted_uids)) unset($session_files[$uid]);
	  
      }
      //remove from field values objects with non posted uids
      foreach ($values as $idx=>$storage_object)
      {
	  $uid = $storage_object->getUID();
	  if (!in_array($uid, $posted_uids)) unset($values[$idx]);
      }

      //merge remaining session files
      foreach($session_files as $uid=>$file) {
	  
	  @$storage_object = unserialize($file);
	  if ($storage_object instanceof StorageObject) {
	    $values[] = $storage_object;
	    debug("Deserialized UID: ".$storage_object->getUID()." append to field values");
	    
	  }
	  else {
	    debug("SessionUploadInputProcessor:: [$uid] could not be deserialized as StorageObject - removing from session array");
	    unset($session_files[$uid]);
	  }

      }

      //reorder
      $values = array_values($values);

      $field->setValue($values);
      
      debugArray("SessionUploadInputProcessor::loadPostData: Final field values including session fiels:", $values);  
      
      debug("SessionUploadInputProcessor::loadPostData Finished");
      debug("-");

  }

  
//   public function beforeCommit(InputField $field, DBTransactor $transactor, DBDriver $db, $item_key)
//   {
//       
//       parent::beforeCommit($field, $transactor, $db, $item_key);
//       
//   }
  public function afterCommit(InputField $field, DBTransactor $transactor)
  {
      $field_name = $field->getName();

      if (isset($_SESSION["upload_control"][$field_name])) {

	  unset($_SESSION["upload_control"][$field_name]);
	  debug("SessionUploadInputProcessor::afterCommit: Cleared Session field['$field_name']");
      }
      if (isset($_SESSION["upload_control_removed"][$field_name])) {
	  unset($_SESSION["upload_control_removed"][$field_name]);
	  debug("SessionUploadInputProcessor::afterCommit: Cleared Session Removed UIDs for field['$field_name']");
      }

  }
  
  public function transactValue(InputField $field, DBTransactor $transactor)
  {	
   
     
      $values = $field->getValue();
      $field_name = $field->getName();

      //transact only UIDs found inside the session array i.e. the new ones
      debug("SessionUploadInputProcessor::transactValue: field['$field_name'] ".gettype($values));
      debug("SessionUploadInputProcessor::transactValue: ".count($values)." values to transact");

      if (!is_null($field->getSource())) {
	$data_source = $field->getSource();
	
	debug("SessionUploadInputProcessor::transactValue | Field uses data source: '".get_class($data_source)."' will commit values in before commit ...");
	return;
      }


      if ($field->transact_mode == InputField::TRANSACT_DBROW) {

	  debug("SessionUploadInputProcessor::transactValue | Transact Mode: TRANSACT_DBROW");
	
	  if (count($values)>1) {
	    throw new Exception("Could not transact multiple objects to the main transaction using TRANSACT_DBROW mode.");
	  }
	  if (count($values)<1) {
	      throw new Exception("Could not transact empty object to the main transaction using TRANSACT_DBROW mode. (effective result will be delete of the main transaction row)");
	  }
	  //expecting single object
	  foreach ($values as $idx=>$storage_object) {
	      $uid = $storage_object->getUID();

	      //this object is the same as the one that was loaded
	      if (array_key_exists($uid, $this->loaded_uids)) {
		  debug("Object with UID: $uid as the same UID as the bean loaded one. Not transacting this object.");
	      }
	      else {
		  debug("Transacting StorageObject UID: $uid merged with the main transaction row ");
		  $dbrow = array();
		  $storage_object->deconstruct($dbrow, $field_name);
		  foreach($dbrow as $key=>$field_value) {
		      $transactor->appendValue($key, $field_value);
		  }
		  debug("SessionUploadInputProcessor::transactValue | Deconstructed UID: $uid as fields in the main transaction row");

	      }
	      break;
	  }
	  

      }
      else if ($field->transact_mode == InputField::TRANSACT_OBJECT) {
	  debug("SessionUploadInputProcessor::transactValue | Transact Mode: MODE_OBJECT");
	  
	  if (count($values)>1) {
	    throw new Exception("Could not transact multiple objects to the main transaction using TRANSACT_OBJECT mode.");
	  }
	  
	  if (count($values)<1) {
	      debug("Field does not contain values. Transacting NULL value to the main transaction row");
	      $value = NULL;
	      $transactor->appendValue($field_name, $value);
	  }
	  
	  //transact the first value if it is not the same as the loaded one
	  foreach ($values as $idx=>$storage_object) {
	      $uid = $storage_object->getUID();

	      
	      if (array_key_exists($uid, $this->loaded_uids)) {
		debug("StorageObject UID: $uid is the same UID as the bean loaded one. Not transacting this object to the main transaction row.");
	      }
	      else {
		debug("Transacting StorageObject UID: $uid serialized to the main transaction row");
		$value = DBDriver::get()->escapeString(serialize($storage_object));
		$transactor->appendValue($field_name, $value);
	      }
	      break;
	  }

      }
      else {
	throw new Exception("Could not transact this field using mode TRANSACT_VALUE");
      }
      
      debug("SessionUploadInputProcessor::transactValue: field['$field_name'] finished values");

  }
}
?>