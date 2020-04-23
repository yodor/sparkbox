<?php
include_once("lib/beans/DBViewBean.php");

class InventoryColorsView extends DBViewBean
{
    protected $createString = "";

    // CREATE VIEW `inventory_colors` AS (
    // select `si`.`color` AS `color`,`si`.`pclrID` AS `pclrID`,`si`.`piID` AS `piID`,`si`.`prodID` AS `prodID`,`si`.`have_chip` AS `have_chip`,`si`.`pclrpID` AS `pclrpID`,`si`.`ppID` AS `ppID`,
    // `sc`.`color_code` AS `color_code`
    // from (`inventory` `si` join `store_colors` `sc` on((`sc`.`color` = `si`.`color`))) where (`si`.`pclrID` is not null) group by `si`.`pclrID`)

    public function __construct()
    {
        parent::__construct("inventory_colors");
        $this->prkey = "piID";
    }

}

?>