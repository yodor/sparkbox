<?php
include_once("lib/beans/ArrayDataBean.php");

class AdminRolesSelector extends ArrayDataBean
{


  protected function initFields() 
  {
      $this->fields=array("roles");
      $this->prkey="roles";
  }
  
  protected function initValues() 
  {
      global $all_roles; //from config/admin_roles.php

      if (is_array($all_roles)) {
	$this->values = array();
	foreach ($all_roles as $key=>$val)
	{
	    $this->values[] = array($this->prkey=>$val);
	}
      }

  }
}
?>