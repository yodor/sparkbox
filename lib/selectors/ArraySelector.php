<?php
include_once("lib/beans/ArrayDataBean.php");

class ArraySelector extends ArrayDataBean
{

  protected $arr = NULL;


  public function __construct(array $arr, $prkey="arr_id", $value_key="arr_val")
  {
      $this->arr=$arr;
      $this->prkey = $prkey;
      $this->value_key=$value_key;

      parent::__construct();

  }
  protected function initFields() 
  {
      $this->fields = array($this->prkey, $this->value_key);

  }
  protected function initValues() 
  {

      $this->values = array();

      foreach ($this->arr as $key=>$val)
      {
	  $this->values[] = array($this->prkey=>$key, $this->value_key=>$val);

      }

  }


}
?>
