<?php
include_once("components/renderers/cells/TableCell.php");

class DateCell extends TableCell
{

    protected string $format = "j F Y\<\B\R\>H:i:s";

    protected TextComponent $dateText;

    public function __construct($format = "j F Y\<\B\R\>H:i:s")
    {
        parent::__construct();
        $this->format = $format;
    }

    public function setData(array $data): void
    {
        parent::setData($data);
        $value = $this->getContents();
        if (strcmp($value, "0000-00-00") === 0 || strlen($value) < 1) {
            $this->setContents("N/A");
        }
        else {
            $time = strtotime($value);
            $text = date($this->format, $time);
            //the name of the month in the language of the site ('25 November 2026' - '25 ноември 2026')
            $month = date("F", $time);
            $local = DateCell::MonthName($time);
            if ($local !== "" && $local !== $month) $text = str_replace($month, $local, $text);
            $this->setContents($text);
        }
    }

    protected static ?IntlDateFormatter $monthFormatter = null;

    /**
     * The name of the month of '$time' in the locale of the site (Config::DEFAULT_LOCALE)
     */
    public static function MonthName(int $time) : string
    {
        if (is_null(DateCell::$monthFormatter)) {
            DateCell::$monthFormatter = new IntlDateFormatter((string)Spark::Get(Config::DEFAULT_LOCALE), IntlDateFormatter::NONE,
                IntlDateFormatter::NONE, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "LLLL");
        }
        $name = DateCell::$monthFormatter->format($time);
        return is_string($name) ? $name : "";
    }

}