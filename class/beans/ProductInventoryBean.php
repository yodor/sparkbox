<?php
include_once ("lib/beans/DBTableBean.php");


class ProductInventoryBean extends DBTableBean
{

    protected $createString = "";
    
    public function __construct() 
    {
	parent::__construct("product_inventory");
    }

}

?>