<?php
include_once("beans/CurrenciesBean.php");
include_once("beans/CurrencyRatesBean.php");

class CurrencyConverter
{

    /**
     * @var float
     */
    protected $rate = 1.0;
    /**
     * @var string ISO3 currency code
     */
    protected $code;

    /**
     * @var string
     */
    protected $symbol;

    /**
     * @var bool
     */
    protected $symbol_back = FALSE;

    /**
     * @var int
     */
    protected $srcID = -1;

    /**
     * @var int
     */
    protected $dstID = -1;

    protected static $instance;

    static public function Instance(): CurrencyConverter
    {
        $result = NULL;
        if (self::$instance instanceof CurrencyConverter) {
            $result = self::$instance;
        }
        else if (Session::Contains("CurrencyConverter")) {
            $result = unserialize(Session::Get("CurrencyConverter"));
        }
        else {
            $result = new CurrencyConverter();
        }

        $result->loadConversion();

        debug("Conversion Currency ID: " . $result->dstID, " - Code: " . $result->code);

        Session::Set("CurrencyConverter", serialize($result));
        return $result;
    }

    protected function loadConversion()
    {
        if (!Session::Contains("currencyID")) {
            return;
        }

        $dstID = (int)Session::Get("currencyID");

        if ($this->dstID == $dstID) {
            return;
        }

        $currencies = new CurrenciesBean();
        $conversion_currency = $currencies->getByID($dstID);

        $currency_rates = new CurrencyRatesBean();
        $qry = $currency_rates->queryFull();
        $qry->select->where()->add("srcID", $this->srcID)->add("dstID", $this->dstID);
        $qry->select->limit = 1;
        $num = $qry->exec();
        if ($num < 1) {
            Session::Set("currencyID", $this->srcID);
            $this->dstID = $this->srcID;
        }

        if ($data = $qry->next()) {
            $this->setCurrency($conversion_currency, (float)$data["rate"]);

        }

    }

    protected function setCurrency(array $data, float $rate = 1.0)
    {
        $this->dstID = (int)$data["currencyID"];
        $this->rate = $rate;
        $this->symbol = $data["symbol"];
        $this->symbol_back = (int)$data["symbol_back"];
        $this->code = $data["currency_code"];
    }

    private function __construct()
    {
        $currencies = new CurrenciesBean();

        if (!Session::Contains("default_currency")) {

            $qry = $currencies->queryFull();
            if (defined("DEFAULT_CURRENCY")) {
                $qry->select->where()->add("currency_code", "'" . DEFAULT_CURRENCY . "'", " LIKE ");
            }
            $qry->select->limit = 1;
            $qry->select->order_by = $currencies->key() . " ASC";
            $num = $qry->exec();
            if ($num < 1) throw new Exception("Unable to set default currency");

            $data = $qry->next();
            $this->srcID = $data[$currencies->key()];
            $this->setCurrency($data, 1.0);
            debug("Default Currency ID: " . $this->srcID, " - Code: " . $this->code);
        }

    }

    public function getValue(float $price): float
    {
        return $price * $this->rate;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function isSymbolBack(): bool
    {
        return $this->symbol_back;
    }

    /**
     * Convert
     * @param float $price
     * @return string
     */
    public function getValueLabel(float $price): string
    {
        return $this->getLabel($this->getValue($price));
    }

    /**
     * Already converted value
     * @param float $price
     * @return string
     */
    public function getLabel(float $converted_price) : string
    {
        $front_symbol = $this->symbol;
        $back_symbol = $this->symbol;
        if ((int)$this->symbol_back>0) {
            $front_symbol = "";
        }
        else {
            $back_symbol = "";
        }

        return $front_symbol . " " . sprintf("%0.2f",$converted_price) . " " . $back_symbol;
    }
}
?>