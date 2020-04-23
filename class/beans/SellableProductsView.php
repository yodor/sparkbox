<?php
include_once("lib/beans/DBViewBean.php");

class SellableProductsView extends DBViewBean
{
    protected $createString = "";

    // create view sellable_products as (
    //     SELECT
    // min(si.sell_price) as price_min, max(si.sell_price) as price_max,
    // (SELECT group_concat(pcp.pclrpID ORDER BY pcp.position SEPARATOR '|') FROM product_color_photos pcp WHERE pcp.pclrID = si.pclrID ) as color_gallery,
    //
    // group_concat(si.piID SEPARATOR '|')  as pids ,
    // group_concat(si.size_value SEPARATOR '|') as size_values,
    // group_concat(si.sell_price SEPARATOR '|') as sell_prices,
    // group_concat(si.stock_amount SEPARATOR '|') as stock_amounts,
    // group_concat(si.old_price SEPARATOR '|') as old_prices,
    //
    // cc.pi_ids as color_pids, cc.colors, cc.color_photos, cc.have_chips, cc.color_ids, cc.product_photos, cc.color_codes,
    // si.piID, si.stock_amount, si.price as i_price, si.old_price as i_old_price, si.buy_price as i_buy_price, si.weight as i_weight, si.size_value, si.pclrID, si.color, si.color_code, si.have_chip, si.pclrpID, si.ppID,
    // si.discount_amount, si.sell_price, si.insert_date as inventory_date,
    // p.*
    //
    // FROM inventory si JOIN products p ON p.prodID = si.prodID LEFT JOIN color_chips cc ON cc.prodID = si.prodID
    // WHERE p.visible = 1
    // GROUP BY  si.prodID, si.pclrID
    // )


    public function __construct()
    {
        parent::__construct("sellable_products");
        $this->prkey = "piID";
    }

}

?>