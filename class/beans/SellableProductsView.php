<?php
include_once ("lib/beans/DBViewBean.php");

class SellableProductsView extends DBViewBean {
    protected $createString = "";
	
	
// create view sellable_products_view as (SELECT 
// (SELECT group_concat(pp.ppID ORDER BY pp.ppID ASC SEPARATOR '|') FROM product_photos pp WHERE pp.prodID = si.prodID) as product_gallery, 
// (SELECT group_concat(pcp.pclrpID ORDER BY pcp.position SEPARATOR '|') FROM product_color_photos pcp WHERE pcp.pclrID = si.pclrID ) as color_gallery, 
// (SELECT group_concat(sp1.size_value SEPARATOR '|') FROM sellable_products sp1 WHERE sp1.pclrID = si.pclrID ) as size_values, 
// (SELECT group_concat(sp1.sell_price SEPARATOR '|') FROM sellable_products sp1 WHERE sp1.pclrID = si.pclrID ) as sell_prices, 
// (SELECT group_concat(sp1.old_price SEPARATOR '|') FROM sellable_products sp1 WHERE sp1.pclrID = si.pclrID ) as old_prices, 
// (SELECT group_concat(sp1.stock_amount SEPARATOR '|') FROM sellable_products sp1 WHERE sp1.pclrID = si.pclrID ) as stock_amounts, 
// (SELECT group_concat(sp1.piID SEPARATOR '|') FROM sellable_products sp1 WHERE sp1.pclrID = si.pclrID ) as pids ,
// cc.pi_ids as color_pids, cc.colors, cc.color_photos, cc.have_chips, cc.color_ids, cc.product_photos,
// si.*
// FROM sellable_products si LEFT JOIN color_chips cc ON cc.prodID = si.prodID GROUP BY si.prodID, si.pclrID)

    public function __construct() 
    {
	  parent::__construct("sellable_products_view");
	  $this->prkey = "piID";
    }

}

?>