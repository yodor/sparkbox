<?php
include_once ("lib/beans/DBTableBean.php");


class ProductColorsBean extends DBTableBean
{
    protected $createString = "";
    
    public function __construct() 
    {
	parent::__construct("product_colors");
    }

}
?>