<?php
include_once ("lib/beans/DBTableBean.php");


class ProductSizesBean extends DBTableBean
{
    protected $createString = "";
    
    public function __construct() 
    {
	parent::__construct("product_sizes");
    }

}
?>