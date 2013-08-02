<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/InputField.php");

class EnumFieldValidator implements IInputValidator
{
	protected $table_name;
	protected $field_name;

	public function __construct($table_name, $field_name=NULL)
	{
		  $this->table_name = $table_name;
		  $this->field_name = $field_name;
	}

	public function validateInput(InputField $field)
	{
	  if (!$this->field_name) $this->field_name = $field->getName();

	  global $g_db;
	  if (!$g_db) throw new Exception("Unable to access global database connection");

	  $ret = $g_db->fieldType($this->table_name, $this->field_name );
	  $ret = $g_db->enum2array($ret);
	  
	  if (!in_array($field->getValue(), $ret)) {

		 throw new Exception("Incorrect value");

	  }
	}

}
?>