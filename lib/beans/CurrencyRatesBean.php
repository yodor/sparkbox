<?php
include_once("beans/DBTableBean.php");

class CurrencyRatesBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `currency_rates` (
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

    public function getPrice($price_value)
    {
        global $currencies;

        //TODO: Set in defines the default currency and convert to the session set currencyID
        $dstID = Session::Get("currencyID");
        $crrow = array();

        try {
            $crrow = $currencies->getByID($dstID);
        }
        catch (Exception $e) {
            $qry = $currencies->queryFull();
            $qry->exec();
            if ($crrow = $qry->next()) {
                $dstID = $crrow[$currencies->key()];
                Session::Set("currencyID", $dstID);
            }
        }

        $ret["price_value"] = 0;
        $ret["symbol"] = $crrow["symbol"];
        $ret["currency_code"] = $crrow["currency_code"];

        $qry = $this->queryFull();
        $qry->select->where()->add("srcID", $dstID)->add("dstID","1");
        $num = $qry->exec();
        if ($row = $qry->next()) {
            $rate = (float)$row["rate"];
            $ret["price_value"] = $price_value * $rate;
        }
        return $ret;

    }

}