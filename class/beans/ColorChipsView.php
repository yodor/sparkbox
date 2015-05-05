<?php
include_once ("lib/beans/DBViewBean.php");

class ColorChipsView extends DBViewBean {
    protected $createString = "";
	
	


// create view color_chips as (select 
// i.prodID,
//  group_concat(ic.piID SEPARATOR '|') as pi_ids, 
//  group_concat(ic.color SEPARATOR '|') as colors, 
//  group_concat(ic.color_code SEPARATOR '|') as color_codes,  
//  group_concat( 
//      
//          (select group_concat(coalesce(ic1.pclrpID,0)) FROM inventory_colors ic1 WHERE ic1.piID=i.piID )
//      		SEPARATOR '|'
//  ) as color_photos, 
//  group_concat(ic.have_chip SEPARATOR '|') as have_chips,
//  group_concat(ic.pclrID SEPARATOR '|') as color_ids,
//  (select group_concat(pp.ppID SEPARATOR '|') FROM product_photos pp WHERE pp.prodID = i.prodID ORDER BY pp.position ASC) as product_photos
// FROM inventory i LEFT JOIN inventory_colors ic ON ic.piID = i.piID GROUP BY i.prodID)

    public function __construct() 
    {
	  parent::__construct("color_chips");
	  $this->prkey = "prodID";
    }

}

?>