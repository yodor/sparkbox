<?php
include_once("beans/DBTableBean.php");

class DatedBean extends DBTableBean
{
    protected IntlDateFormatter $formatter;

    protected string $date_column = "";


    /**
     * DatedPublicationBean constructor.
     * @param string $table_name
     * @param string $datefield
     * @throws Exception
     */
    public function __construct(string $table_name, string $datefield = "item_date")
    {
        parent::__construct($table_name);
        if (!$this->haveColumn($datefield)) throw new Exception("Date column not found in this bean table");
        $this->date_column = $datefield;

        $this->formatter = new IntlDateFormatter(
            locale:          Spark::Get(Config::DEFAULT_LOCALE),
            dateType:        IntlDateFormatter::NONE,   // no date part
            timeType:        IntlDateFormatter::NONE,   // no time part
            timezone:        'UTC',                     // timezone usually irrelevant here
            calendar:        IntlDateFormatter::GREGORIAN,
            pattern:         "d MMMM y"
        );

    }

    public function setDefaultOrder(SQLSelect $select) : void
    {
        $select->order($this->date_column, OrderDirection::DESC);
        $select->order($this->prkey , OrderDirection::DESC);
    }

    public function getDateColumn(): string
    {
        return $this->date_column;
    }

    /**
     * Default pattern is "d MMMM y"
     * Default locale is Spark::Get(Config::DEFAULT_LOCALE) or en_US
     *
     * @return IntlDateFormatter
     */
    public function getDateFormatter() : IntlDateFormatter
    {
        return $this->formatter;
    }

    public function formatDate(int $timestamp, ?string $format=null) : string
    {
        $currentPatter = $this->formatter->getPattern();

        if ($format) {
            $this->formatter->setPattern($format);
        }

        $result = $this->formatter->format($timestamp);

        $this->formatter->setPattern($currentPatter);

        return $result;
    }


    public function queryID(int $itemID, string ...$columns) : SelectQuery
    {
        $query = $this->query($this->key(), ...$columns);
        $query->stmt->where()->match($this->prkey, $itemID);
        $this->setDefaultOrder($query->stmt);

        return $query;
    }

    public function queryDefault(string ...$columns) : SelectQuery
    {
        $query = $this->query($this->key(), ...$columns);
        $this->setDefaultOrder($query->stmt);
        return $query;
    }


    /**
     * Query publications for specified month and year
     * @param int $year
     * @param int $month
     * @param string ...$columns
     * @return SelectQuery
     * @throws Exception
     */
    public function queryMonthYear(int $year, int $month, string ...$columns) : SelectQuery
    {
        if ($year<1) throw new Exception("Year must be greater than 0");

        if ($month<1 || $month>12) throw new Exception("Incorrect month number");
        $query = $this->query($this->key(), ...$columns);

        $query->stmt->where()->expression("MONTH($this->date_column) = :month");
        $query->stmt->where()->bind(":month", $month);

        $query->stmt->where()->expression("YEAR($this->date_column) = :year");
        $query->stmt->where()->bind(":year", $year);

        $this->setDefaultOrder($query->stmt);

        return $query;
    }

    /**
     * Return array of all days for a given $year and $month that have publication
     * @param int $year
     * @param int $month
     * @return array<int> 1-31
     * @throws Exception
     */
    public function days(int $year, int $month) : array
    {

        $query = $this->queryMonthYear($year, $month);

        $query->stmt->alias("DAY($this->date_column)", "day");

        $query->stmt->group_by = " DAY($this->date_column) ";

        $query->exec();

        $data = array();
        while ($result = $query->next()) {
            $data[] = (int)$result["day"];
        }
        $query->free();

        return $data;
    }

    /**
     * Return array of all months of $year having publications
     * @return array<int> 1-12
     * @throws Exception
     */
    public function months(int $year): array
    {
        if ($year<1) throw new Exception("Year must be greater than 0");

        $query = $this->query($this->key());
        $query->stmt->alias("MONTH($this->date_column)", "month");
        $query->stmt->where()->expression("YEAR($this->date_column) = :year");
        $query->stmt->where()->bind(":year", $year);
        $this->setDefaultOrder($query->stmt);
        $query->stmt->group_by = " MONTH($this->date_column) ASC ";

//        $query->stmt->setMeta("MonthsQuery");

        $query->exec();

        $data = array();
        while ($result = $query->next()) {
            $data[] = (int)$result["month"];
        }
        $query->free();

        return $data;
    }
    /**
     * return array of all years having publications
     * @return array<int>
     * @throws Exception
     */
    public function years(): array
    {
        $query = $this->query($this->key());
        $query->stmt->alias("YEAR($this->date_column)", "year");
        $this->setDefaultOrder($query->stmt);
        $query->stmt->group_by = " YEAR($this->date_column) DESC ";

//        $query->stmt->setMeta("YearsQuery");

        $query->exec();

        $data = array();
        while ($result = $query->next()) {
            $data[] = (int)$result["year"];
        }
        $query->free();

        return $data;
    }



    /**
     * Return the number of publications in a given month and year
     *
     * @param int $year Year ex.2020
     * @param int $month Month ex.6 (1-12)
     * @return int
     * @throws Exception
     */
    public function countBy(int $year, int $month): int
    {
        return $this->queryMonthYear($year, $month)->count();
    }

    /**
     * Returns full or abbreviated month name in the desired locale
     *
     * @param int    $monthNumber  1–12
     * @param bool   $short        false = full name, true = abbreviated (3 letters)
     * @return string
     */
    public static function MonthName(int $monthNumber, bool $short = false): string
    {
        if ($monthNumber < 1 || $monthNumber > 12) {
            throw new InvalidArgumentException('Month must be between 1 and 12');
        }

        $pattern = $short ? 'MMM' : 'MMMM';

        $formatter = new IntlDateFormatter(
            locale:          Spark::Get(Config::DEFAULT_LOCALE),
            dateType:        IntlDateFormatter::NONE,
            timeType:        IntlDateFormatter::NONE,
            timezone:        'UTC',
            calendar:        IntlDateFormatter::GREGORIAN,
            pattern:         $pattern
        );

        // Most reliable: use DateTime + format('U') → integer timestamp
        $dt = new DateTime();
        $dt->setDate(2000, $monthNumber, 1);
        $dt->setTime(12, 0, 0);           // noon avoids midnight DST issues

        return $formatter->format($dt);
    }
    // 	public function pastEventsBefore($year, $month, $limit=1){
    // 			$select = "SELECT SQL_CALC_FOUND_ROWS *, MONTH({$this->datefield}) AS month, YEAR({$this->datefield}) AS year FROM {$this->table} WHERE {$this->filter} AND ( MONTH({$this->datefield})<$month and YEAR({$this->datefield})=$year) OR  YEAR({$this->datefield})<$year ORDER BY {$this->datefield} DESC LIMIT $limit";
    // 			$total = -1;
    // 			$this->iterator = $this->createIterator($select, $total);
    // 			return $total;
    // 			//$this->iterator = $db->query($select);
    // 			//$ret = $db->query("SELECT FOUND_ROWS() AS total");
    // 			//$row = $db->fetch($ret);
    // 			//return $row["total"];
    //
    //
    //
    // 	}
    // 	public function futureEventsAfter($year, $month, $limit=1){
    // 			$select = "SELECT SQL_CALC_FOUND_ROWS *, MONTH({$this->datefield}) AS month, YEAR({$this->datefield}) AS year FROM {$this->table} WHERE {$this->filter} AND ( MONTH({$this->datefield})>$month AND YEAR({$this->datefield})=$year) OR YEAR({$this->datefield})>$year ORDER BY {$this->datefield} ASC LIMIT $limit";
    // 			$total = -1;
    // 			$this->iterator = $this->createIterator($select, $total);
    // 			return $total;
    // 			//$this->iterator = $db->query($select);
    // 			//$ret = $db->query("SELECT FOUND_ROWS() AS total");
    // 			//$row = $db->fetch($ret);
    // 			//return $row["total"];
    // 	}
}