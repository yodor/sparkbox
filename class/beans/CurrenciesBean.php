<?php
include_once("lib/beans/DBTableBean.php");

class CurrenciesBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `currencies` (
 `currencyID` int(10) unsigned NOT NULL auto_increment,
 `currency_code` varchar(3) collate utf8_unicode_ci NOT NULL,
 `symbol` varchar(10) collate utf8_unicode_ci NOT NULL,
 PRIMARY KEY  (`currencyID`),
 UNIQUE KEY `currency_code` (`currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

    public function __construct()
    {
        parent::__construct("currencies");
        $this->na_str = false;
        $this->na_val = "";
    }


}
