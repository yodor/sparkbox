<?php
include_once ("lib/beans/DBViewBean.php");

class ColorChipsView extends DBViewBean {
    protected $createString = "";
	
/*	
create view color_chips as (select 
 ic.prodID,
 group_concat(ic.piID SEPARATOR '|') as pi_ids, 
 group_concat(ic.color SEPARATOR '|') as colors,  
 group_concat(ic.pclrpID SEPARATOR '|') as color_photos, 
 group_concat(ic.have_chip SEPARATOR '|') as have_chips,
 group_concat(ic.pclrID SEPARATOR '|') as color_ids,
 (select group_concat(pp.ppID SEPARATOR '|') FROM product_photos pp WHERE pp.ppID = ic.prodID ORDER BY position ASC) as product_photos
FROM inventory_colors ic LEFT JOIN sellable_products spv2 ON spv2.piID = ic.piID  GROUP BY ic.prodID)*/



    public function __construct() 
    {
	  parent::__construct("color_chips");
	  $this->prkey = "prodID";
    }

}

?>