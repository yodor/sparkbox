<?php
include_once ("lib/beans/DBTableBean.php");

class InventoryAttributeValuesBean extends DBTableBean
{
    protected $createString = "";
    
    public function __construct() 
    {
	  parent::__construct("inventory_attribute_values");
    }

}

?>
