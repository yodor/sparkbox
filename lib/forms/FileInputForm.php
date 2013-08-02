<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/input/InputFactory.php");

class FileInputForm extends InputForm
{

	public $accept_mimes = NULL;

	public function __construct($accept_mimes=false) {
	  parent::__construct();

$field = new InputField("data", "File", 1);
$field->setValidator(new FileUploadValidator());
$field->setProcessor(new UploadDataInputProcessor());
$field->setRenderer(new FileField());
$this->addField($field);

$field = new InputField("caption", "Caption", 0);
$field->setRenderer(new TextField());
$this->addField($field);

	  if (!$accept_mimes) {
		  $this->accept_mimes = array(
"application/acrobat",
"application/x-pdf",
"application/pdf",
"application/rtf",
"application/msword",
"application/msexcel",
"application/vnd.oasis.opendocument.text",
"application/vnd.oasis.opendocument.spreadsheet",
"application/vnd.oasis.opendocument.presentation",
"application/vnd.oasis.opendocument.graphics",
"application/vnd.ms-excel",
"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
"application/vnd.ms-powerpoint",
"application/vnd.openxmlformats-officedocument.presentationml.presentation",
"application/vnd.openxmlformats-officedocument.wordprocessingml.document",

);
	  }

$this->getField("data")->getValidator()->setAcceptMimes($this->accept_mimes);

	}



 }
?>