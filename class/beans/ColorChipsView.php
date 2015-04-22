<?php
include_once ("lib/beans/DBViewBean.php");

class ColorChipsView extends DBViewBean {
    protected $createString = "";
	
/*	
create view color_chips as (select 
 spv2.prodID,
 group_concat(ic.piID SEPARATOR '|') as pi_ids, 
 group_concat(ic.color SEPARATOR '|') as colors,  
 group_concat(ic.pclrpID SEPARATOR '|') as color_photos, 
 group_concat(ic.have_chip SEPARATOR '|') as have_chips,
 group_concat(ic.pclrID SEPARATOR '|') as color_ids,
 (select group_concat(pp.ppID SEPARATOR '|') FROM product_photos pp WHERE pp.prodID = spv2.prodID ORDER BY pp.position ASC) as product_photos
FROM sellable_products spv2 LEFT JOIN inventory_colors ic ON spv2.piID = ic.piID  GROUP BY spv2.prodID)*/



    public function __construct() 
    {
	  parent::__construct("color_chips");
	  $this->prkey = "prodID";
    }

}

?>