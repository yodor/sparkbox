<?php
include_once("beans/DBTableBean.php");

class DatedBean extends DBTableBean
{

    protected string $date_column;

    /**
     * DatedPublicationBean constructor.
     * @param string $table_name
     * @param string $datefield
     * @throws Exception
     */
    public function __construct(string $table_name, string $datefield = "item_date")
    {
        parent::__construct($table_name);
        if (!$this->haveColumn($datefield)) throw new Exception("Date field not found in this bean table");
        $this->date_column = $datefield;

    }

    public function getDateColumn(): string
    {
        return $this->date_column;
    }

    /**
     * return array of all years having publications
     * @return array
     * @throws Exception
     */
    public function getYears(): array
    {

        $qry = $this->query($this->key());
        $qry->select->fields()->setExpression(" YEAR($this->date_column) ", "year");
        $qry->select->order_by = " $this->date_column DESC ";
        $qry->select->group_by = " YEAR($this->date_column) DESC  ";
        $qry->exec();

        $data = array();
        while ($row = $qry->next()) {
            $data[] = $row["year"];
        }
        return $data;
    }

    /**
     * Return array of all publications for given month and year
     * @param string $d_year
     * @param string $d_month
     * @return array
     * @throws Exception
     */
    public function filterMonthList(string $d_year, string $d_month)
    {

        $qry = $this->query($this->key());
        $qry->select->where()->add("MONTH($this->date_column)", "'$d_month'")->add("YEAR($this->date_column)", "'$d_year'");
        $qry->select->order_by = " $this->date_column DESC ";
        $qry->exec();

        $data = array();

        while ($row = $qry->next()) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Return the day part of the publications date in a given month and year
     * @param string $d_year
     * @param string $d_month
     * @return array
     * @throws Exception
     */
    public function filterDayList(string $d_year, string $d_month)
    {

        $qry = $this->query($this->key());
        $qry->select->fields()->setExpression("DAY($this->date_column)", "day");
        $qry->select->where()->add("MONTH($this->date_column)", "'$d_month'")->add("YEAR($this->date_column)", "'$d_year'");
        $qry->exec();

        $data = array();

        while ($row = $qry->next()) {
            $data[] = $row["day"];
        }

        return $data;
    }

    /**
     * Return the number of publications in a given month and year
     *
     * @param string $d_year Year ex.2020
     * @param string $d_month Month ex.6
     * @return int
     * @throws Exception
     */
    public function publicationsCount(string $d_year, string $d_month): int
    {
        $qry = $this->query($this->key());
        $qry->select->where()->add("MONTH($this->date_column)", "'$d_month'")->add("YEAR($this->date_column)", "'$d_year'");
        $qry->select->limit = " 1 ";
        return $qry->exec();
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

?>
