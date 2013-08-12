<?php
include_once("lib/input/InputField.php");
include_once("lib/input/InputFactory.php");


class InputForm {

	//holds the values of fields
	protected $fields=array();

	public $star_required = true;

	protected $edit_bean;
	protected $edit_id;

	protected $form_processor = NULL;
	protected $form_renderer = NULL;


	public function __construct()
	{

		$this->edit_bean = NULL;
		$this->edit_id = -1;


	}

	public function getRenderer()
	{
		return $this->form_renderer;
	}
	public function setRenderer(IFormRenderer $form_renderer)
	{
		$this->form_renderer = $form_renderer;
	}

	public function setProcessor(IFormProcessor $form_processor)
	{
		$this->form_processor = $form_processor;
	}
	public function getProcessor()
	{
		return $this->form_processor;
	}
	public function addField(InputField $field)
	{
		$field->setForm($this);
		$this->fields[$field->getName()]=$field;
	}

	public function insertFieldAfter(InputField $field, $after_field_name)
  {

	$keys = array_keys($this->fields);
	$index = array_search($after_field_name, $keys);

	$field_name = $field->getName();


	$this->fields = array_slice($this->fields, 0, $index+1, true) +
    array("$field_name" => $field) +
    array_slice($this->fields, $index+1, count($this->fields) - 1, true) ;

  }

	public function removeField($field_name)
	{
		if (isset($this->fields[$field_name])) {
			unset($this->fields[$field_name]);
		}
	}

	public function setEditBean(DBTableBean $bean)
	{
		$this->edit_bean=$bean;
	}

	public function getEditBean()
	{
		return $this->edit_bean;
	}

	public function setEditID($editid)
	{
		$this->edit_id = $editid;
	}

	public function getEditID()
	{
		return $this->edit_id;
	}

	public function haveField($field_name) {
		return array_key_exists($field_name,$this->fields);
	}

	public function fieldExists($field_name)
	{
		if (!$this->haveField($field_name)) throw new Exception("InputField [$field_name] is not defined in this form: ".get_class());
		return $this->fields[$field_name];
	}

	public function getField($field_name)
	{
		return $this->fieldExists($field_name);
	}


// 	public function getValuesArray()
	public function getFieldValues()
	{
		$ret = array();

		foreach ($this->fields as $field_name => $field) {
			  $ret[$field_name]=$field->getValue();
		}
		return $ret;
	}

	public function getFields()
	{
		return $this->fields;
	}


	public function valueUnescape($field_name)
	{
		  $field = $this->fieldExists($field_name);
		  return mysql_real_unescape_string($field->getValue());
	}

	public function haveErrors()
	{
		  $found_error = false;
		  foreach ($this->fields as $field_name => $field) {
			  if ($field->haveError() === true) {
				$found_error=true;
				break;
			  }
		  }
		  return $found_error;
	}

	public function clear()
	{
		foreach ($this->fields as $field_name => $field)
		{
			$field->clear();
		}
	}



	public function loadPostData(array $arr)
	{
	    foreach ($this->fields as $field_name=>$field) {
		if ($field->isEditable()) {
		  $field->loadPostData($arr);

		}
	    }

	}
	
	public function validate()
	{
	    foreach ($this->fields as $field_name=>$field) {

		if ($field->isEditable()) {
		    $field->validate();
		}
	    }
	}
	
	public function loadBeanData($editID, DBTableBean $bean)
	{
	    debug("InputForm::loadBeanData: editID='$editID' ".get_class($bean));
	    
	    //TODO: check if setEditBean and editID is used anymore
	    $this->setEditBean($bean);
	    $this->setEditID($editID);

	    
	    if ($editID>0) {
	      debug("InputForm::loadBeanData: Edit/Update mode ");
	      $item_row = $bean->getByID($editID);
	      $item_key = $bean->getPrKey();
	      
	      //do not validate values comming from db
	      //$this->load($item_row);
	      
	      
	      //initial loading of bean data
	      foreach ($this->fields as $field_name=>$field) {
		  debug("InputForm::loadBeanData: loading field: $field_name");

		  //processor need value set. processor might need other values from the item_row or to parse differently the value
		  $field->getProcessor()->loadBeanData($editID, $bean, $field, $item_row);
	      }

	    }
	    else {
	      debug("InputForm::loadBeanData: Add/Insert mode ");
	    }

	}

	public  function searchFilterArray()
	{
		$search_filter=array();


		foreach ($this->fields as $field_name=>$field){

			if ($field->skip_search_filter_processing) continue;

			$val = $field->getValue();

			if ($val>-1 && strcmp($val,"")!=0) {

				$field_name = str_replace("|",".", $field_name);

				$sffk = $this->searchFilterForKey($field_name,$val);
				if ($sffk)
					$search_filter[]=$sffk;
			}
		}
		return $search_filter;
	}

	protected function searchFilterForKey($key,$val)
	{
		return "$key='$val'";
	}

	public  function searchFilter($type=" WHERE ")
	{
		$sa = $this->searchFilterArray();
		$sf = "";
		if (count($sa)>0){
			$sf = " $type ".implode($sa," AND ");
		}
		return $sf;
	}
	public  function searchFilterQuery()
	{
		$sel = new SelectQuery();
		$sel->fields = "";
		$sel->from = "";

		$sa = $this->searchFilterArray();
		$sf = "";
		if (count($sa)>0){
			$sf = implode($sa," AND ");
		}
		$sel->where = $sf;
		return $sel;
	}
	public function serializeXML()
	{
	    ob_start();
	    echo "<?xml version='1.0' encoding='utf-8'?>";
	    echo "<inputform class='".get_class($this)."'>";
	    echo "<fields>";
	    foreach ($this->fields as $field_name=>$field) {
	      echo "<field>";
		echo "<name>$field_name</name>";
		echo "<value>".$field->getValue()."</value>";
	      echo "</field>";
	    }
	    echo "</fields>";
	    echo "</inputform>";
	    $xml = ob_get_contents();
	    ob_end_clean();
	    return $xml;
	}
	public function unserializeXML($xml_string)
	{
	    $inputform = @simplexml_load_string($xml_string);
	    if (!$inputform) throw new Exception("Unable to parse input as XML");
	    
	    foreach ($inputform->fields->field as $field) {
		$name = (string)$field->name;
		$value = (string)$field->value;
// 		echo $name."=>".$value;
		if (!$this->haveField($name)) continue;
		
		$this->getField($name)->setValue($value);
	    }

	    
	}
	public function dumpErrors()
	{
		foreach ($this->fields as $field_name=>$field) {

if ($field->haveError()) {
	echo "$field_name:";
	var_dump($field->getValue());
	echo "<HR>";
	echo "Error: ";
	var_dump($field->getError())."<BR>";
	}

		}
	}
	public function dumpForm()
	{

	    foreach ($this->fields as $field_name=>$field) {
		if ($field instanceof UploadDataInputFormField) continue;
		echo $field->getLabel().": ".$field->getValue()."<br><BR>\r\n\r\n";

	    }
	}
	

	
}
?>