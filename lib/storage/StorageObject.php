<?php

class StorageObject {

  protected $data = NULL;
  protected $length = -1;
  protected $timestamp = 0; //date_time
  protected $uid = -1;
  protected $upload_status = NULL;

  public function __construct()
  {
	  $this->data = NULL;
	  $this->length = -1;
	  $this->timestamp = 0;
	  $this->uid = microtime(true).".".rand();
	  
	  
  }
  public function getUploadStatus()
  {
	  return $this->upload_status;
  }
  public function setUploadStatus($status)
  {
	  $this->upload_status = $status;
  }
  public function setTimestamp($timestamp) {
	  $this->timestamp = $timestamp;
  }
  public function getTimestamp() {
	  return $this->timestamp;
  }
  public function getData()
  {
	  return $this->data;
  }
  public function getLength()
  {
	  return $this->length;
  }
  public function setLength($length)
  {
	  $this->length = $length;
  }
  public function setData($data)
  {
	  $this->data=$data;
	  $this->length = strlen($data);
  }
  
  public function haveData()
  {
	  return (strlen($this->data)>0);
  }
  public function serializeDB()
  {

	  return DBDriver::get()->escapeString(serialize($this));

  }
  public function getUID()
  {
      return (string)$this->uid;
  }
  public function setUID($uid)
  {
      $this->uid=$uid;
  }
  
  public function deconstruct(array &$row, $data_key="data", $doEscape=true)
  {
	  $row[$data_key]=$this->data;

	  if ($doEscape) {
		
		
		  $row[$data_key] = DBDriver::get()->escapeString($this->data);
		
	  }
	  $row["size"]=$this->length;
	  $row["date_upload"]=$this->timestamp;

  }
  
  public static function reconstruct(&$row, $field_name)
  {
   
      $storage_object = NULL;
      if ( isset($row["mime"]) && isset($row["size"]) && isset($row[$field_name]) && isset($row["filename"]) && isset($row["date_upload"]) ) {

	    debug("StorageObject::reconstruct | Found needed array key to reconstruct a storage object");

	    if ( isset($row["width"]) && isset($row["height"]) ) {
	      $storage_object = new ImageStorageObject();
	      $storage_object->setData($row[$field_name]);
	      debug("StorageObject::reconstruct | Reconstructed ImageStorageObject: Dimensions (".$row["width"]."x".$row["height"].")");
	    }
	    else {
	      $storage_object = new FileStorageObject();
	      $file_storage->setData($row[$field_name]);
	      debug("StorageObject::reconstruct | Reconstructed FileStorageObject");
	    }

	    $storage_object->setMIME($row["mime"]);
	    $storage_object->setFilename($row["filename"]);
	    $storage_object->setTimestamp($row["date_upload"]);
	    
	    $storage_object->setUploadStatus(UPLOAD_ERR_OK);
	    
	    $reconstructed_uid = $row[$field_name].$row["mime"]."|".$row["size"]."|".$row["filename"];
	    
	    $storage_object->setUID(strtotime($storage_object->getTimestamp()).".".md5($reconstructed_uid));
	    
	    debug("StorageObject::reconstruct | Reconstructed properties: UID: ".$storage_object->getUID()." MIME: ".$row["mime"]." Filename: ".$row["filename"]." Length: ".$row["size"]);

	    return $storage_object;
      }
      else {
	  throw new Exception("Unable to reconstruct from this row. Required fields not found");
      }

  }
}
?>