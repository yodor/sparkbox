<?php
include_once ("lib/beans/OrderedDataBean.php");

class ProductColorPhotosBean  extends OrderedDataBean
{
    protected $createString = "";
    
    public function __construct() 
    {
	  parent::__construct("product_color_photos");
    }

}
?>
