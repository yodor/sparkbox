<?php
include_once ("lib/beans/DBViewBean.php");

class SellableProductsBean extends DBViewBean {
    protected $createString = "";
	
	
// create view sellable_products as (SELECT p.*, 
//  coalesce(sp.discount_percent,0) as discount_amount ,
//  p.price - (p.price * (coalesce(sp.discount_percent,0)) / 100.0) as sell_price
//  FROM sellable_inventory p LEFT JOIN store_promos sp ON (sp.targetID = p.catID AND sp.target='Category' AND sp.start_date < NOW() AND sp.end_date > NOW())  WHERE p.visible=1)

// create view sellable_products as (SELECT p.*, si.piID, si.color, si.size_value, si.pclrID, si.pszID,
//  coalesce(sp.discount_percent,0) as discount_amount ,
//  si.price - (si.price * (coalesce(sp.discount_percent,0)) / 100.0) as sell_price
//  FROM sellable_inventory si LEFT JOIN products p ON p.prodID = si.prodID LEFT JOIN store_promos sp ON (sp.targetID = p.catID AND sp.target='Category' AND sp.start_date < NOW() AND sp.end_date > NOW())  WHERE p.visible=1)
//  
    public function __construct() 
    {
	  parent::__construct("sellable_products");
	  $this->prkey = "prodID";
    }

}

?>