<?php
include_once("lib/beans/DBTableBean.php");

class DatedPublicationBean extends DBTableBean
{

    protected $datefield;

    public function __construct($table_name, $datefield = "item_date")
    {
        parent::__construct($table_name);
        $this->datefield = $datefield;

    }

    public function getYearsArray()
    {


        $this->startIterator(" GROUP BY YEAR({$this->datefield}) DESC ", " YEAR({$this->datefield}) AS year  ");

        $years_array = array();
        $row = array();
        while ($this->fetchNext($row)) {
            $years_array[] = $row["year"];
        }
        return $years_array;
    }

    public function filterDayList($d_year, $d_month)
    {


        $this->startIterator(" WHERE month({$this->datefield})='$d_month' AND  YEAR({$this->datefield})=$d_year ", " DAY({$this->datefield}) AS day ");

        $ar = array();
        $row = array();
        while ($this->fetchNext($row)) {
            $ar[] = $row["day"];
        }

        return $ar;
    }

    public function filterMonthList($d_year, $d_month)
    {


        $this->startIterator(" WHERE MONTHNAME({$this->datefield})='$d_month' AND YEAR({$this->datefield})=$d_year ORDER BY {$this->datefield} DESC ", " * ");

        $ar = array();
        $row = array();
        while ($this->fetchNext($row)) {
            $ar[] = $row;
        }

        return $ar;
    }

    public function containsDataForMonth($d_year, $d_month)
    {
        $found = false;

        $found = $this->startIterator(" WHERE monthname({$this->datefield})='$d_month' and year({$this->datefield})='$d_year' ", " * ");

        return $found;

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