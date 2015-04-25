<?php
include_once ("lib/beans/DBViewBean.php");

class InventoryColorsView extends DBViewBean {
    protected $createString = "";
	

// create view inventory_colors as (select si.color, si.pclrID, si.piID, si.prodID, si.have_chip, si.pclrpID, si.ppID FROM inventory si WHERE si.pclrID IS NOT NULL GROUP BY si.pclrID)

    public function __construct() 
    {
	  parent::__construct("inventory_colors");
	  $this->prkey = "piID";
    }

}

?>