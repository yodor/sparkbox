<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/InputField.php");

abstract class UploadDataValidator implements IInputValidator
{
  protected $maxsize=-1;
  protected $accept_mimes=array();

  public $skip_is_uploaded_check = false;

  //in ArrayInputField case Processors get called only once, but valdators are called for each array value
  public function __construct()
  {
  
  }

  public function setAcceptMimes($accept_mimes)
  {
	$this->accept_mimes=$accept_mimes;
  }

  public static function errString($err, $maxsize=0)
  {
	$ret = "Undefined error: $err";
	switch ($err) {
	    case UPLOAD_ERR_OK:
		    $ret = "There is no error, the file uploaded with success.";
		    break;
	    case UPLOAD_ERR_INI_SIZE:
		    $ret = "The uploaded file exceeds the init limit of ".UPLOAD_MAX_FILESIZE;
		    break;
	    case UPLOAD_ERR_FORM_SIZE:
		    $ret = "The uploaded file exceeds the form limit of $maxsize";
		    break;
	    case UPLOAD_ERR_PARTIAL:
		    $ret = "The uploaded file was only partially uploaded";
		    break;
	    case UPLOAD_ERR_NO_FILE:
		    $ret = "No file was selected for upload";
		    break;
	    case UPLOAD_ERR_NO_TMP_DIR:
		    $ret = "Missing a temporary folder";
		    break;
	    case UPLOAD_ERR_CANT_WRITE:
		    $ret = "Failed to write file to disk";
		    break;
	}
	return $ret;
  }

  public function validateInput(InputField $field)
  {


      if ($this->maxsize < 1){
	      $this->maxsize = UPLOAD_MAX_FILESIZE;
      }
      if ($this->maxsize > UPLOAD_MAX_FILESIZE) {
	      $this->maxsize = UPLOAD_MAX_FILESIZE;
      }

      $content_length = 0;
      if (isset($_SERVER["CONTENT_LENGTH"])) {
	      $content_length = $_SERVER['CONTENT_LENGTH'];
      }
      //$_FILES array is always empty if post size > maxsize so check additionally here to be able to give correct error message
      if ($content_length > $this->maxsize) {
	      throw new Exception(UploadDataValidator::errString(UPLOAD_ERR_FORM_SIZE, file_size($this->maxsize)));
      }

      //UploadDataInputProcessor always created one FileStroageObject with error_status UPLOAD_ERR_NO_FILE
      $file_storage = $field->getValue();
      debug("UploadDataValidator::validateInput: field['".$field->getName()."'] - class: ".get_class($file_storage));
      
      $upload_status = $file_storage->getUploadStatus();
      
      debug("UploadDataValidator::validateInput: field['".$field->getName()."'] - UploadStatus: ".UploadDataValidator::errString($upload_status));
      
      if ($upload_status===UPLOAD_ERR_NO_FILE) {
	if ($field->isRequired()) {
	      if (!$field->getForm() || $field->getForm()->getEditID()<1) {
		throw new Exception(UploadDataValidator::errString($upload_status));
	      }
	}
	return;
      }

      if ($upload_status!==UPLOAD_ERR_OK) {
	  throw new Exception("Upload error:<br> ".UploadDataValidator::errString($upload_status, $this->maxsize));
      }

      if ($this->skip_is_uploaded_check) {
      }
      else {
	  if(!is_uploaded_file($file_storage->getTempName())) {
		  throw new Exception("Not an uploaded file");
	  }
      }

      if ( $file_storage->getLength() > $this->maxsize ){
	      // if the file is not less than the maximum allowed, print an error
	      throw new Exception("File exceeds the maximum file limit of {$this->maxsize} bytes.<br>File ".$file_storage->getFilename()." is
	      ".$row["size"]." bytes.");
      }

      if (count($this->accept_mimes)>0 && !in_array($file_storage->getMIME(),$this->accept_mimes)){
	      throw new Exception("Wrong File Type: ".$file_storage->getMIME(). ".<Br>Required Type: ".implode(';',$this->accept_mimes));
      }

      $this->processUploadData($field);

  }
  abstract protected function processUploadData(InputField $field);

//   abstract protected function getStorageObject();



}
?>