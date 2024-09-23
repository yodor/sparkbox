<?php
include_once("beans/DBTableBean.php");

class CurrenciesBean extends DBTableBean
{
    protected string $createString = "CREATE TABLE `currencies` (
 `currencyID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `currency_code` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
 `symbol` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
 `symbol_back` tinyint(1) NOT NULL DEFAULT 0,
 PRIMARY KEY (`currencyID`),
 UNIQUE KEY `currency_code` (`currency_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

    public function __construct()
    {
        parent::__construct("currencies");

    }

}