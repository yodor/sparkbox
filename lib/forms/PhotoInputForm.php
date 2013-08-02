<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/input/InputFactory.php");

class PhotoInputForm extends InputForm
{

	public function __construct() 
	{
	    parent::__construct();

	    $field = new InputField("caption", "Caption", 0);
	    $field->setRenderer(new TextField());
	    $this->addField($field);

	    
	    $field = InputFactory::CreateField(InputFactory::SESSION_IMAGE, "photo", "Photo", 1);
	    $this->addField($field);

	}
	


}
?>