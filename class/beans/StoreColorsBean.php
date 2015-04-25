<?php
include_once ("lib/beans/DBTableBean.php");


class StoreColorsBean extends DBTableBean
{
    protected $createString = "";
    
    public function __construct() 
    {
	parent::__construct("store_colors");
    }

}
?>