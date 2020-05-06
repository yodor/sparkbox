<?php
include_once("beans/DBTableBean.php");

class DatedPublicationBean extends DBTableBean
{

    protected $datefield;

    /**
     * DatedPublicationBean constructor.
     * @param $table_name
     * @param string $datefield
     * @throws Exception
     */
    public function __construct($table_name, $datefield = "item_date")
    {
        parent::__construct($table_name);
        $this->datefield = $datefield;

    }

    public function getYearsArray()
    {

        $qry = $this->query();
        $qry->select->fields = " YEAR({$this->datefield}) AS year  ";
        $qry->select->group_by = " YEAR({$this->datefield}) DESC  ";
        $qry->exec();

        $years_array = array();
        while ($row = $qry->next()) {
            $years_array[] = $row["year"];
        }
        return $years_array;
    }

    public function filterDayList($d_year, $d_month)
    {

        $qry = $this->query();
        $qry->select->fields = " DAY({$this->datefield}) AS day  ";
        $qry->select->where = " month({$this->datefield})='$d_month' AND  YEAR({$this->datefield})=$d_year ";
        $qry->exec();

        $ar = array();

        while ($row = $qry->next()) {
            $ar[] = $row["day"];
        }

        return $ar;
    }

    public function filterMonthList($d_year, $d_month)
    {

        $qry = $this->query();
        $qry->select->where = " MONTHNAME({$this->datefield})='$d_month' AND YEAR({$this->datefield})=$d_year ";
        $qry->select->order_by = " {$this->datefield} DESC ";
        $qry->exec();

        $ar = array();

        while ($row = $qry->next()) {
            $ar[] = $row;
        }

        return $ar;
    }

    public function containsDataForMonth($d_year, $d_month)
    {
        $qry = $this->query();
        $qry->select->where = " MONTHNAME({$this->datefield})='$d_month' AND YEAR({$this->datefield})='$d_year' ";
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