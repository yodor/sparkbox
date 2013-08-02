<?php
include_once("lib/forms/InputForm.php");

class AuthForm extends InputForm {

	public function __construct()
	{
		parent::__construct();

		$field = InputFactory::CreateField(InputFactory::TEXTFIELD, "username", "Email", 1);
		$this->addField($field);

		$field = InputFactory::CreateField(InputFactory::TEXTFIELD_PASSWORD, "password", "Password", 1);
		$this->addField($field);

		$field = InputFactory::CreateField(InputFactory::HIDDEN, "rand", "rand", 1);
		$this->addField($field);

		$field = InputFactory::CreateField(InputFactory::HIDDEN, "pass", "pass_hash", 1);
		$this->addField($field);

	}


}
?>