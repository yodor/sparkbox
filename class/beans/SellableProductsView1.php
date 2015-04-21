<?php
include_once ("lib/beans/DBViewBean.php");

class SellableProductsView extends DBViewBean {
    protected $createString = "";
	
	
// create view sellable_products_view1 as (SELECT pcp.pclrpID, 
// (SELECT group_concat(pp.ppID SEPARATOR '|') FROM product_photos pp WHERE pp.prodID = si.prodID) as product_photos, 
// (SELECT group_concat(pcp1.pclrpID SEPARATOR '|') FROM product_color_photos pcp1 WHERE pcp1.pclrID = si.pclrID ) as photos, 
// (SELECT group_concat(sp1.size_value SEPARATOR '|') FROM sellable_products sp1 WHERE sp1.pclrID = si.pclrID ) as size_values, 
// (SELECT group_concat(sp1.sell_price SEPARATOR '|') FROM sellable_products sp1 WHERE sp1.pclrID = si.pclrID ) as sell_prices, 
// (SELECT group_concat(sp1.stock_amount SEPARATOR '|') FROM sellable_products sp1 WHERE sp1.pclrID = si.pclrID ) as stock_amounts, 
// (SELECT group_concat(sp1.piID SEPARATOR '|') FROM sellable_products sp1 WHERE sp1.pclrID = si.pclrID ) as pids,
// (SELECT group_concat(length(pc1.color_photo)>0 SEPARATOR '|') FROM product_colors pc1 WHERE pc1.pclrID = si.pclrID) as have_color_chips,
// (SELECT group_concat(pc2.color SEPARATOR '|') FROM product_colors pc2 WHERE pc2.prodID = si.prodID AND (pc2.pclrID NOT IN (si.pclrID)) ) as other_colors,
// 
// si.* 
// FROM sellable_products si LEFT JOIN product_color_photos pcp on pcp.pclrID = si.pclrID LEFT JOIN product_sizes psz ON psz.pszID = si.pszID WHERE si.prodiD=1 GROUP BY si.prodID ORDER BY pcp.pclrpID DESC)

    public function __construct() 
    {
	  parent::__construct("sellable_products_view1");
	  $this->prkey = "piID";
    }

}

?>