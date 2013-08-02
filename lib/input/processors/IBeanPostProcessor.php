<?php
include_once("lib/beans/DBTableBean.php");
include_once("lib/input/InputField.php");



interface IBeanPostProcessor
{

  
  public function loadBeanData($editID, DBTableBean $bean, InputField $field,  array $item_row);
  public function loadPostData(InputField $field, array $arr);
 
  

}

?>