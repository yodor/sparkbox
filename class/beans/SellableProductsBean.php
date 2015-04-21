<?php
include_once ("lib/beans/DBViewBean.php");

class SellableProductsBean extends DBViewBean {
    protected $createString = "";
	
	
// create view sellable_products as (SELECT si.*, 
//   coalesce(sp.discount_percent,0) as discount_amount ,
//   si.price - (si.price * (coalesce(sp.discount_percent,0)) / 100.0) as sell_price
//   FROM sellable_inventory si LEFT JOIN store_promos sp ON (sp.targetID = si.catID AND sp.target='Category' AND sp.start_date < NOW() AND sp.end_date > NOW()) )
//   
//  

    public function __construct() 
    {
	  parent::__construct("sellable_products");
	  $this->prkey = "piID";
    }

}

?>