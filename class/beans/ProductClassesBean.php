<?php
include_once ("lib/beans/DBTableBean.php");

class ProductClassesBean extends DBTableBean
{

    protected $createString = "";
    
    public function __construct() 
    {
	parent::__construct("product_classes");
    }

}

?>