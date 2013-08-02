<?php
include_once("lib/input/processors/IBeanPostProcessor.php");
include_once("lib/input/transactors/IDBFieldTransactor.php");

class BeanPostProcessor implements IBeanPostProcessor, IDBFieldTransactor
{

  //keeps map of storage_object UID to data_source primary key value
  protected $source_loaded_uids = array();
  
  protected $source_loaded_keys = array();
  
  public $process_datasource_foreign_keys = false;
  
  public function __construct()
  {
    
  }

  public function beforeCommit(InputField $field, DBTransactor $transactor, DBDriver $db, $item_key)
  {
      $data_source = $field->getSource();
      if (!($data_source instanceof DBTableBean)) {
	debug("Data source is null or not from expected class DBTableBean nothing to do in beforeCommit");
	return;
      }
      
      
      $source_key = $data_source->getPrKey();
      $lastID = $transactor->getLastID();

      $field_name = $field->getName();
      
      debug("BeanPostProcessor::beforeCommit | Using data Source: ".get_class($data_source)." | Field: $field_name | lastID: $lastID | Source PrimaryKey: $source_key | Field Values count: ".count($field->getValue()));

      
      $foreign_transacted = 0;

      if (count($field->getValue())<1) {
	  
	  debug("BeanPostProcessor::beforeCommit | 0 values to transact to data source. Clearing all rows of the data source: ".get_class($data_source));
	  $data_source->deleteRef($item_key, $transactor->getLastID(), $db);
	  
	  return;
      }

      if ($field->transact_mode == InputField::TRANSACT_VALUE) {
	    debug("BeanPostProcessor::beforeCommit | Transact Mode: TRANSACT_VALUE");
// 	    $data_source->deleteRef($item_key, $lastID, $db);
	    debug("BeanPostProcessor::beforeCommit | Merging updated values ...");

	    //TODO:try to update data source found in source_loaded_values. Delete removed values. Keep order of loaded
	    $processed_ids = array();
	    
	    foreach ($field->getValue() as $idx=>$value) {
	    
		$dbrow = array();
		$dbrow[$item_key] = $transactor->getLastID();
		
		if (is_array($value))throw new Exception("Could not transact value of type Array using transact mode TRANSACT_VALUE to data_source");
		if ($value instanceof StorageObject) throw new Exception("Could not transact value of type StorageObject using transact mode TRANSACT_VALUE to data_source");
		
		$dbrow[$field_name] = $value;

		  //
		$sourceID = array_shift($this->source_loaded_keys);
		if ($sourceID>0) {
		  debug("DataSourceID: ".$sourceID);
		  
		  if (!$data_source->updateRecord($sourceID, $dbrow, $db)) throw new Exception("Unable to update  data source bean. Error: ".$db->getError());
		  $processed_ids[] = $sourceID;
		}
		else {
		
		  //append posted foreign keys
		  if ($this->process_datasource_foreign_keys) {
		    if (isset($_REQUEST["fk_$field_name"][$idx])) {
			$fks = $_REQUEST["fk_$field_name"][$idx];
			$fk_pairs = explode("|", $fks);
			foreach($fk_pairs as $fk_idx=>$fk_pair) {
			    list($fk_name, $fk_value) = explode(":",$fk_pair);
			    $dbrow[$fk_name] = $fk_value;
			}
		    }
		  }
		  $refID = $data_source->insertRecord($dbrow, $db);
		  if ($refID<1) throw new Exception("Unable to insert into data source bean. Error: ".$db->getError());
		  $processed_ids[] = $refID;
		}
		$foreign_transacted++;

	    }

	    //TODO:duplicate keys might get triggered
	     $data_source->deleteRef($item_key, $transactor->getLastID(), $db, $processed_ids);
      }
      else  {
      
	    debug("Processing for non TRANSACT_VALUE modes ... ");
		    
	    debugArray("Current source loaded UIDs dump: ", $this->source_loaded_uids);
	    
	    $processed_ids = array();
	    
	    foreach ($field->getValue() as $idx=>$value) {

		if (! ($value instanceof StorageObject)) throw new Exception("Transact mode TRANSACT_DBROW can transact only values of type  StorageObject");

		$uid = $value->getUID();
		
		debug("Processing UID: $uid");
		
		
		if (!array_key_exists($uid, $this->source_loaded_uids)) {
		    debug("StorageObject UID: $uid not found in the loaded keys array. Will commit insert operation to data source ...");
		    
		    //new value need insert
		    $dbrow = array();
		    $dbrow[$item_key] = $transactor->getLastID();
		    
		    if ($field->transact_mode == InputField::TRANSACT_DBROW) {
		      debug("BeanPostProcessor::beforeCommit | Transact Mode: TRANSACT_DBROW");
		      $value->deconstruct($dbrow, $field_name);
		      debug("BeanPostProcessor::beforeCommit | StorageObject UID: $uid deconstructed as fields in the data source row ...");
		    
		    }
		    else if ($field->transact_mode == InputField::TRANSACT_OBJECT) {
		      debug("BeanPostProcessor::beforeCommit | Transact Mode: TRANSACT_OBJECT");
		      $dbrow[$field_name] = $db->escapeString(serialize($value));
		      debug("BeanPostProcessor::beforeCommit | StorageObject UID: $uid stored as serialized value in the data source row ...");
		    }
		    else {
		      throw new Exception("Unknown transact mode: ".$field->transact_mode);
		    }
		    
		    $refID = $data_source->insertRecord($dbrow, $db);
		    if ($refID<1) throw new Exception("Unable to insert into data source bean. Error: ".$db->getError());
		    $foreign_transacted++;
		    
		    $processed_ids[] = $refID;
		    
		    debug("BeanPostProcessor::beforeCommit | StorageObject UID: $uid transacted to data source with ID: ".$refID);
		}
		else {
		    //skip transaction. same uid
		    $processed_ids[] = $this->source_loaded_uids[$uid];
		}
		
	    }
	    
	    debugArray("Processed data source keys dump: ", $processed_ids);

	    //delete remaining values - datasource values with keys not found in processed_ids
	    $data_source->deleteRef($item_key, $transactor->getLastID(), $db, $processed_ids);

	    debug("Remaining data source keys removed");

      }

      debug("BeanPostProcessor::beforeCommit | Total $foreign_transacted rows transacted to data source: ".get_class($data_source));

  }
  public function afterCommit(InputField $field, DBTransactor $transactor)
  {
      //
  }
  //
  
  public function transactValue(InputField $field, DBTransactor $transactor)
  {
      switch ($field->transact_mode) {
	case InputField::TRANSACT_VALUE:
	  $transactor->appendValue($field->getName(), $field->getValue());
	  break;
	case InputField::TRANSACT_DBROW:
	  throw new Exception("Unsupported TRANSACT_DBROW for input field['".$field->getName()."']");
	  break;
	case InputField::TRANSACT_OBJECT:
	  throw new Exception("Unsupported TRANSACT_OBJECT for input field['".$field->getName()."']");
	  break;
	default:
	  throw new Exception("Unsupported transaction mode for input field['".$field->getName()."']");
      }
      
  }
  protected function processRowData(InputField $field,  &$item_row, $source_key)
  {
      $value = null;
      $field_name = $field->getName();
      
      if ($field->transact_mode == InputField::TRANSACT_VALUE) {

	  $value = $item_row[$field_name];
	  $this->source_loaded_keys[] = $item_row[$source_key];
	  debug("SourceLoaded value for datasource keyID: ".$item_row[$source_key]);
	  
      }
      if ($field->transact_mode == InputField::TRANSACT_DBROW) {

	  $value = StorageObject::reconstruct($item_row, $field_name);
	  $uid = $value->getUID();
	  
	  $source_key_value = $item_row[$source_key];
	  
	  $this->source_loaded_uids[$uid] = $source_key_value;
	  
	  debug("Source Load UID: Reconstructed StorageObject UID: $uid From data source $source_key='$source_key_value'");
	  
      }
      else if ($field->transact_mode == InputField::TRANSACT_OBJECT) {

	  $value = $item_row[$field_name];
	  
	  $storage_object = @unserialize($value);
	  if ($storage_object !== false ) {
	      if (!($storage_object instanceof StorageObject)) throw new Exception("Deserialized object is not a StorageObject.");
	      $value = $storage_object;
	      
	      $uid = $value->getUID();
	  
	      $source_key_value = $item_row[$source_key];
	      
	      $this->source_loaded_uids[$uid] = $source_key_value;
	      
	      debug("Source Load UID:  Deserialized StorageObject UID: $uid From data source $source_key='$source_key_value'");
	  
	  }
	  else {
	      if (!is_null($value)) throw new Exception("Expected serialized contents in '$field_name' of data source: ".get_class($field->getSource()));
	  }
      }
      return $value;
  }
  
  
  //called from InputFormView to load the field data from bean fields
  public function loadBeanData($editID, DBTableBean $bean, InputField $field,  array $item_row)
  {
      $field_name = $field->getName();
      $item_key = $bean->getPrKey();
      debug("BeanPostProcessor::loadBeanData | field[$field_name] for bean: ".get_class($bean)." | item_key: $item_key");
      
      
      $values = array();
      
      
      if (array_key_exists($field_name, $item_row)) {

	  debug("BeanPostProcessor::loadBeanData: '$field_name' found in the item_row - loading value");
	  
	  $value = $item_row[$field_name];
	  
	  if ($field->transact_mode == InputField::TRANSACT_OBJECT) {
	    //non required fields holdings storage objects can load NULL values, remove them as they dont need presentation
	    if (!is_null($value)) {
	      $storage_object = @unserialize($value);
	      if ($storage_object !== false ) {
		  if (!($storage_object instanceof StorageObject)) throw new Exception("Deserialized object is not a StorageObject.");
		  $value = $storage_object;
	      }
	    }
	  }
	  else if ($field->transact_mode == InputField::TRANSACT_DBROW){
	    $object = StorageObject::reconstruct($item_row, $field_name);
	    $uid = $object->getUID();
	    $value = $object;
	  }
	  
	  if ($field instanceof ArrayInputField) {
	    $values = array($value);
	    
	  }
	  else {
	    $values = $value;
	  }
	  
      }
      else {
      
	  
	  //process data source values
	  debug("BeanPostProcessor::loadBeanData: '$field_name' not found in item_row - trying field source values");

	  $data_source = $field->getSource();
	  
	  if (is_null($data_source)) {
	      debug("BeanPostProcessor::loadBeanData: '$field_name' no data source is set for this field");
	      $values = null;
	  }
	  else {
	  
	    debug("BeanPostProcessor::loadBeanData: field: '$field_name' | Using Data Source: ".get_class($data_source));

	    if (!($data_source instanceof DBTableBean)) throw new Exception("Received data source: '".get_class($data_source)."' but 'DBTableBean' expected.");

	    $source_fields = $data_source->getFields();
	    $source_key = $data_source->getPrKey();
	    
	    if (!in_array($item_key, $source_fields)) throw new Exception("ItemKey: $item_key not exist in data source fileds");
	    
	    debug("BeanPostProcessor::loadBeanData: processing data_source values using access field: '$item_key'");

	    $source_values = array();
	    $data_source->startFieldIterator($item_key, $editID);
	    

	    while ($data_source->fetchNext($row)) {
	    
		debug("DataSourceLoadID: $source_key=>".$row[$source_key]);
		
		$source_values[] = $this->processRowData($field, $row, $source_key);

	    }

	    debugArray("BeanPostProcessor:loadBeanData | Data source values loaded: ", $source_values);
	    $values = $source_values;
	    
	  }

      }//array_key_exists
      
//       debugArray("SimpleInputProcessor::loadBeanData: | Final field values: ",$values);
      $field->setValue($values);
	
    }
	
    //called from post
    public function loadPostData(InputField $field, array $arr)
    {

	  $field_name = $field->getName();

	  //sanitize non-compound fields
	  if (array_key_exists($field_name, $arr)) {

	      $value = $arr[$field_name];

	      $value = sanitizeInput($value, $field->accepted_tags);

	      $field->setValue($value);

	  }

    }

}
?>