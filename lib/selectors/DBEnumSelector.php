<?php
include_once("lib/beans/ArrayDataBean.php");

class DBEnumSelector extends ArrayDataBean
{
  private $table_name;
  private $table_field;

  public function __construct($table_name, $table_field)
  {
      $this->table_name = $table_name;
      $this->table_field = $table_field;

      parent::__construct();

  }
  protected function initFields() {

	$this->fields=array($this->table_field);
	$this->prkey=$this->table_field;
  }
  protected function initValues() 
  {
	
	$db = DBDriver::get();
	
	$ret = $db->fieldType($this->table_name, $this->table_field);
	$ret = $db->enum2array($ret);

	$this->values = array();
	foreach ($ret as $key=>$val)
	{
		$this->values[] = array($this->table_field=>$val);
	}
  }


}
?>