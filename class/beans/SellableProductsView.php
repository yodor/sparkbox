<?php
include_once ("lib/beans/DBViewBean.php");

class SellableProductsView extends DBViewBean {
    protected $createString = "";
	
	
/*
SELECT 

(SELECT group_concat(pcp.pclrpID ORDER BY pcp.position SEPARATOR '|') FROM product_color_photos pcp WHERE pcp.pclrID = si.pclrID ) as color_gallery, 

group_concat(si.piID SEPARATOR '|')  as pids ,
group_concat(ps.size_value SEPARATOR '|') as size_values,
group_concat(si.sell_price SEPARATOR '|') as sell_prices,
group_concat(si.stock_amount SEPARATOR '|') as stock_amounts,
group_concat(si.old_price SEPARATOR '|') as old_prices,

cc.pi_ids as color_pids, cc.colors, cc.color_photos, cc.have_chips, cc.color_ids, cc.product_photos,
si.*
FROM sellable_products si LEFT JOIN color_chips cc ON cc.prodID = si.prodID LEFT JOIN product_sizes ps ON ps.pszID = si.pszID GROUP BY  si.prodID, si.pclrID*/

    public function __construct() 
    {
	  parent::__construct("sellable_products_view");
	  $this->prkey = "piID";
    }

}

?>