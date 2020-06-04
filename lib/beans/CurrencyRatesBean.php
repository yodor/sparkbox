<?php
include_once("beans/DBTableBean.php");
include_once("beans/CurrenciesBean.php");

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

        $currencies = new CurrenciesBean();

        if (!Session::Contains("default_currencyID")) {

            $qry = $currencies->queryFull();
            if (defined("DEFAULT_CURRENCY")) {
                $qry->select->where()->add("currency_code", "'" . DEFAULT_CURRENCY . "'", " LIKE ");
            }
            $qry->select->limit = 1;
            $qry->select->order_by = $currencies->key() . " ASC";
            $num = $qry->exec();
            if ($num < 1) throw new Exception("CurrenciesBean is empty");

            $data = $qry->next();
            $currencyID = (int)$data[$currencies->key()];
            Session::Set("default_currencyID", $currencyID);
            debug("Using default_currencyID: $currencyID");

        }
    }

    public function getPrice($price_value)
    {
        global $currencies;

        //TODO: Set in defines the default currency and convert to the session set currencyID
        $srcID = Session::Get("default_currencyID", 1);

        $src_row = $currencies->getByID($srcID);

        $ret["price_value"] = $price_value;
        $ret["symbol"] = $src_row["symbol"];
        $ret["currency_code"] = $src_row["currency_code"];

        if (Session::Contains("currencyID")) {

            $dstID = (int)Session::Get("currencyID");

            try {
                $dst_row = $currencies->getByID($dstID);
            }
            catch (Exception $ex) {
                debug("Requested dst currencyID: '$dstID' was not found");
                Session::Set("currencyID", $srcID);
                return $ret;
            }

            $qry = $this->queryFull();
            $qry->select->where()->add("srcID", $srcID)->add("dstID", $dstID);
            $qry->select->limit = 1;

            if ($qry->exec() && $data = $qry->next()) {
                $rate = (float)$data["rate"];
                $ret["price_value"] = $price_value * $rate;
                $ret["symbol"] = $dst_row["symbol"];
                $ret["currency_code"] = $dst_row["currency_code"];
            }

        }

        return $ret;

    }

}