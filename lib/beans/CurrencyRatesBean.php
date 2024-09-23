<?php
include_once("beans/DBTableBean.php");


class CurrencyRatesBean extends DBTableBean
{
    protected string $createString = "CREATE TABLE `currency_rates` (
 `crID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `srcID` int(11) unsigned NOT NULL,
 `dstID` int(11) unsigned NOT NULL,
 `rate` float NOT NULL DEFAULT '1',
 PRIMARY KEY (`crID`),
 KEY `srcID` (`srcID`),
 KEY `dstID` (`dstID`),
 CONSTRAINT `currency_rates_ibfk_1` FOREIGN KEY (`srcID`) REFERENCES `currencies` (`currencyID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `currency_rates_ibfk_2` FOREIGN KEY (`dstID`) REFERENCES `currencies` (`currencyID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("currency_rates");

    }

}