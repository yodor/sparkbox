<?php
include_once ("lib/beans/DBViewBean.php");

class SellableInventoryBean extends DBViewBean {
    protected $createString = "";
	
// select pi.piID, pi.prodID, pi.stock_amount, 
// if (pi.price>0, pi.price, p.price) as price, 
// if (pi.old_price>0, pi.old_price, p.old_price) as old_price, 
// if (pi.buy_price>0, pi.buy_price, p.buy_price) as buy_price, 
// if (pi.weight>0, pi.weight, p.weight) as weight,
// psz.size_value, pclr.color, pclr.color_photo,
// p.product_name, p.brand_name, pc.category_name, p.product_code, p.product_description, p.keywords, p.gender, p.catID, p.view_counter, p.order_counter, p.visible, p.promotion, p.importID, p.update_date, p.insert_date  FROM product_inventory pi RIGHT JOIN products p ON p.prodID = pi.prodID LEFT JOIN product_categories pc on pc.catID=p.catID LEFT JOIN product_colors pclr ON pclr.prodID=pi.prodID LEFT JOIN product_sizes psz ON psz.prodID = pi.prodID

// SELECT pi.prodID, pi.piID
// if (pi.price>0, pi.price, p.price) as price, 
// if (pi.old_price>0, pi.old_price, p.old_price) as old_price, 
// if (pi.buy_price>0, pi.buy_price, p.buy_price) as buy_price, 
// if (pi.weight>0, pi.weight, p.weight) as weight,
// pclr.color, psz.size_value, pc.category_name, p.product_name FROM product_inventory pi LEFT JOIN product_colors pclr ON pclr.pclrID = pi.pclrID LEFT JOIN product_sizes psz ON psz.pszID = pi.pszID 

    public function __construct() 
    {
	  parent::__construct("sellable_inventory");
	  $this->prkey = "pivID";
    }

}

?>