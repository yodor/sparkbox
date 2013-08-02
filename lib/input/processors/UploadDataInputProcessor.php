<?php
include_once("lib/input/processors/BeanPostProcessor.php");
include_once("lib/storage/FileStorageObject.php");

class UploadDataInputProcessor extends BeanPostProcessor
{

    public function loadPostData(InputField $field, array $arr)
    {

	$name = $field->getName();

	$file_storage = new FileStorageObject();
	$file_storage->setUploadStatus(UPLOAD_ERR_NO_FILE);

	debug("UploadDataInputProcessor:: checking files array with key: ".$name);
	
	debug("UploadDataInputProcessor:: ".implode("|",array_keys($_FILES)));
	
	if (isset($_FILES[$name])) {

	 
	  if (is_array($_FILES[$name]["name"])) {
	  
	       debug("UploadDataInputProcessor:: processing array for name: ".$name);
	       
	      $file_storage = array();

	      $upload = $this->diverse_array($_FILES[$name]);

	      foreach($upload as $idx=>$file) {
		$storage = new FileStorageObject();
		$storage->setUploadStatus(UPLOAD_ERR_NO_FILE);
		$this->processImpl($file, $storage);
		$file_storage[] = $storage;

	      }
	  }
	  else {
	      
	      $this->processImpl($_FILES[$name], $file_storage);
	  }

	}
	else {
	    debug("UploadDataInputProcessor:: no key set for name: ".$name);
	}

	$field->setValue($file_storage);

    }

    protected function processImpl($file, FileStorageObject $file_storage)
    {
	
	$upload_status = $file["error"];

	$file_storage->setUploadStatus($upload_status);

	debug("UploadDataInputProcessor::processImpl: upload_status: ".$upload_status);
	
	if ($upload_status===UPLOAD_ERR_OK) {
	    $temp_name = $file['tmp_name'];
	    $file_storage->setTempName($temp_name);

	    $file_storage->setData(file_get_contents($temp_name));

	    $file_storage->setFilename($file['name']);
	    $file_storage->setLength($file['size']);
	    $file_storage->setMIME($file['type']);


	    global $g_db;
	    if ($g_db) {
	      $file_storage->setTimestamp($g_db->dateTime());
	    }
	    else {
	      $file_storage->setTimestamp(date("Y-m-d H:m:i"));

	    }

	}
    }

    protected function diverse_array($vector)
    {
	$result = array();
	foreach($vector as $key1 => $value1)
		foreach($value1 as $key2 => $value2)
			$result[$key2][$key1] = $value2;
	return $result;
    }
}
?>