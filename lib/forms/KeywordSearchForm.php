<?php
include_once ("lib/forms/InputForm.php");

class KeywordSearchForm extends InputForm
{

// $table_fields = array("cart_data","delivery_details", "order_identifier", "client_identifier", "orderID");

	
	protected $ts_fields = false;

	public function __construct(array $table_fields)
	{
	    parent::__construct();
	    $this->ts_fields = $table_fields;

	    $field = new InputField("keyword","Keyword",0);
	    $field->setRenderer(new TextField());
	    $this->addField($field);

	}
	
	protected  function searchFilterForKey($key,$val)
	{
	    $db = DBDriver::factory();
	    $val=$db->escapeString($val);
	    if (strcmp($key,"keyword")==0){
		    $allwords = explode(" ",$val);

		    $qry = array();

		    foreach ($allwords as $pos=>$keyword) {
		      $ret = array();
		      foreach ($this->ts_fields as $pos1=>$field_name){

			      $ret[] = " $field_name LIKE '%$keyword%' ";
		      }
		      $qry[] = "( ".implode(" OR ", $ret)." )";
		    }
		    
		    return "( ".implode(" AND ",$qry)." )";
	    }
	    else {
		    return parent::searchFilterForKey($key,$val);
	    }
	}
	
	public function clearQuery(&$qryarr)
	{
		foreach($this->fields as $field_name=>$field){
			if (isset($qryarr[$field_name])){
				unset($qryarr[$field_name]);
			}
		}
		unset($qryarr["clear"]);

	}
}
?>