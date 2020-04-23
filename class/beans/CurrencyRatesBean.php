<?php
include_once("lib/beans/DBTableBean.php");

class CurrencyRatesBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `currency_rates` (
 `ccID` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `srcID` int(10) unsigned NOT NULL,
 `dstID` int(10) unsigned NOT NULL,
 `rate` float NOT NULL DEFAULT '1',
 PRIMARY KEY (`ccID`),
 KEY `srcID` (`srcID`),
 KEY `dstID` (`dstID`),
 CONSTRAINT `currency_rates_ibfk_1` FOREIGN KEY (`srcID`) REFERENCES `currencies` (`currencyID`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `currency_rates_ibfk_2` FOREIGN KEY (`dstID`) REFERENCES `currencies` (`currencyID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("currency_rates");
    }

    public function getPrice($price_value)
    {
        global $currencies;

        $ret = array("price_value" => $price_value, "symbol" => "", "currency_code" => "");

        $dstID = (int)Session::Get("currencyID");
        $crrow = array();

        $srcID = (int)Session::Get("currency_defaultID");

        $currencies->startIterator(" WHERE currency_code = '" . DEFAULT_CURRENCY . "'");
        if ($currencies->fetchNext($crrow)) {
            $srcID = $crrow[$currencies->key()];
            Session::Set("currency_defaultID", $srcID);
            $ret["symbol"] = $crrow["symbol"];
            $ret["currency_code"] = $crrow["currency_code"];
        }
        else {
            //default currency was not found
            Session::Set("alert", "Requested default currency [" . DEFAULT_CURRENCY . "] is not available.");
            return $ret;
        }


        try {
            $crrow = $currencies->getByID($dstID);
            $ret["currency_code"] = $crrow["currency_code"];
            $ret["symbol"] = $crrow["symbol"];
        }
        catch (Exception $e) {

            //Session::set("alert", "Requested currencyID: $dstID was not found.");

            $dstID = $srcID;
        }


        $num = $this->startIterator(" WHERE srcID='$dstID' AND dstID='$srcID' ");
        if ($this->fetchNext($row)) {
            $rate = (float)$row["rate"];
            $ret["price_value"] = $price_value * $rate;

        }
        else {
            $ret["price_value"] = $price_value;
        }
        return $ret;

    }

}
