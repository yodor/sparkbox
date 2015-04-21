<?php
include_once ("lib/beans/DBViewBean.php");

class InventoryColorsView extends DBViewBean {
    protected $createString = "";
	
	
// create view inventory_colors as (select 
// si.color, si.pclrID, si.piID, si.prodID,  coalesce(length(pc.color_photo)>0, 0) as have_chip,
// (select pcp.pclrpID FROM product_color_photos pcp WHERE pcp.pclrID = si.pclrID ORDER BY pcp.position ASC LIMIT 1) as pclrpID,
// (select pp.ppID FROM product_photos pp WHERE pp.prodID = si.prodID ORDER BY pp.position ASC LIMIT 1) as ppID
// from 
// sellable_inventory si , product_colors pc WHERE pc.pclrID=si.pclrID GROUP BY si.pclrID)

    public function __construct() 
    {
	  parent::__construct("inventory_colors");
	  $this->prkey = "piID";
    }

}

?>