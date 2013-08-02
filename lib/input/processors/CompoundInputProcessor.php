<?php
include_once("lib/input/processors/BeanPostProcessor.php");
 
class CompoundInputProcessor extends BeanPostProcessor
{

  protected $concat_char = "-";
  protected $compound_names = array();
  protected $compound_values = array();
  
  public function __construct()
  {
	$this->compound_values = array();

	//reset to default value -1
	foreach($this->compound_names as $idx=>$subname)
	{
	    $this->compound_values[$subname] = -1;
	}
  }
  

  public function loadPostData(InputField $field, array $arr) 
  {


	$field_name = $field->getName();

	
	foreach($this->compound_names as $idx => $subname)
	{
	    $compound_name = $subname."_".$field_name; //ex for field birthdate => year_birthdate, month_birthdate, day_birthdate
	    
	    if (array_key_exists($compound_name, $arr)) {
		$value = $arr[$compound_name];
		
		$value = sanitizeInput($value);
		
		if (is_array($value)) {
		  $value = reorderArray($value);
		}
		
		$this->compound_values[$subname] = $value;
	    }

	}
	
	//array case check. InputField can have checkbox 
	if (is_array($this->compound_values[$this->compound_names[0]])) {

	    $compound_count = count($this->compound_values[$this->compound_names[0]]);

	    $arr_compound = array();
	    
	    for ($a=0;$a<$compound_count;$a++) {

		$compound = array();
		foreach($this->compound_names as $key=>$val) {

		    $compound[] = $this->compound_values[$val][$a];
		}
		$arr_compound[] = implode($this->concat_char, $compound);

	    }

	    $field->setValue($arr_compound);

	}
	else {

	    $field->setValue(implode($this->concat_char, $this->compound_values));

	}


  }

}

?>