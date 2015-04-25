<?php
include_once ("lib/beans/DBViewBean.php");

class SellableInventoryBean extends DBViewBean {
    protected $createString = "";
	
// create view inventory as (select pi.piID, pi.prodID, pi.stock_amount, pi.price, pi.old_price, pi.buy_price, pi.weight, pi.size_value, pclr.pclrID, pclr.color, sc.color_code, coalesce(length(pclr.color_photo)>0, 0) as have_chip, (select pcp.pclrpID FROM product_color_photos pcp WHERE pcp.pclrID = pi.pclrID ORDER BY pcp.position ASC LIMIT 1) as pclrpID, (select pp.ppID FROM product_photos pp WHERE pp.prodID = pi.prodID ORDER BY pp.position ASC LIMIT 1) as ppID, coalesce(sp.discount_percent,0) as discount_amount , pi.price - (pi.price * (coalesce(sp.discount_percent,0)) / 100.0) as sell_price FROM product_inventory pi LEFT JOIN product_colors pclr ON pclr.pclrID = pi.pclrID LEFT JOIN store_colors sc ON sc.color=pclr.color LEFT JOIN products p ON p.prodID = pi.prodID LEFT JOIN store_promos sp ON (sp.targetID = p.catID AND sp.target='Category' AND sp.start_date < NOW() AND sp.end_date > NOW()) )


    public function __construct() 
    {
	  parent::__construct("inventory");
	  $this->prkey = "piID";
    }

}

?>