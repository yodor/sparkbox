<?php
include_once("lib/input/renderers/SessionUploadField.php");

class SessionImageField extends SessionUploadField
{

  public function renderArrayContents()
  {

	$field_name = $this->field->getName();

	$images = $this->field->getValue();

	
	
	if (!$this->ajax_handler) {
	  echo "<div class='ArrayContents'>";
	  echo "<div class='error'>Upload Handler not registered</div>";
	  echo "</div>";
	  return;
	}

	$validator = $this->ajax_handler->createValidator(UploadControlAjaxHandler::VALIDATOR_IMAGE);
	

	echo "<div class='ArrayContents'>";
	
	foreach ($images as $idx=>$storage_object) {
	  

// 	  if(is_null($storage_object) || $storage_object->isPurged())continue;
	  if(is_null($storage_object))continue;
	  
	  $validator->processImage($storage_object);

	  $ret = $this->ajax_handler->createUploadContents($storage_object, $field_name);
	  
	  echo $ret["html"];

	}
	echo "</div>";
  }
    
  public function __construct()
  {
      parent::__construct();

      $this->setFieldAttribute("validator","image");

      $this->setClassName("SessionImageField SessionUpload UploadControl");
  }

  
  
}
?>