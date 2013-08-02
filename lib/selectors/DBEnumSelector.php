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
	global $g_db;
	if (! ($g_db instanceof DBDriver))throw new Exception("Global database connection not accessible");
	
	
	$ret = $g_db->fieldType($this->table_name, $this->table_field);
	$ret = $g_db->enum2array($ret);

	$this->values = array();
	foreach ($ret as $key=>$val)
	{
		$this->values[] = array($this->table_field=>$val);
	}
  }


}
?>