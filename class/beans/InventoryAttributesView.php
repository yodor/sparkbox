<?php
include_once("lib/beans/DBViewBean.php");

class InventoryAttributesView extends DBViewBean
{
    protected $createString = "";

    //CREATE  VIEW `inventory_attributes` AS (select `ca`.`class_name` AS `class_name`,`ca`.`attribute_name` AS `attribute_name`,`iav`.`value` AS `value`,`ca`.`default_value` AS `default_value`,`iav`.`piID` AS `piID`, `iav`.`cavID` AS `cavID`, `iav`.`caID` AS `caID`, `i`.`prodID` AS `prodID` from ((`store_demo`.`inventory_attribute_values` `iav` left join `store_demo`.`inventory` `i` on((`i`.`piID` = `iav`.`piID`))) join `store_demo`.`class_attributes` `ca` on((`ca`.`caID` = `iav`.`caID`))))

    public function __construct()
    {
        parent::__construct("inventory_attributes");
        $this->prkey = "cavID";
    }

}

?>
