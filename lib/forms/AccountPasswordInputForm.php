<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/input/InputField.php");


class AccountPasswordInputForm extends InputForm
{

	public function __construct()
	{
	  
		$field = new InputField("cpass", "Current Password",0);
		$field->setRenderer(new PasswordField());
		$field->setScriptRequired(true);
		$this->addField($field);

		$field = 	new InputField("pass", "New Password",0);
		$field->setRenderer(new PasswordField());
		$field->setScriptRequired(true);
		$this->addField($field);

		$field = new InputField("pass1","Repeat Password",0);
		$field->setRenderer(new PasswordField());
		$field->setScriptRequired(true);
		$this->addField($field);

		$field = new InputField("cpassword", "Current Password MD5", 0);
		$field->setRenderer(new HiddenField());
		$this->addField($field);

		$field = new InputField("password","Create Password MD5",0);
		$field->setRenderer(new HiddenField());
		$this->addField($field);

		$field = new InputField("password1","Repeat Password MD5", 0); 
		$field->setRenderer(new HiddenField());
		$this->addField($field);

		$field = new InputField("rand", "Salt", 0);
		$field->setRenderer(new HiddenField());
		$this->addField($field);
	
	}


	
}
?>