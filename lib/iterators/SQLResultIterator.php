<?php
include_once("lib/iterators/SQLIterator.php");

class SQLResultIterator implements SQLIterator
{


    private $select = NULL;
    private $res;
    /**
     * @var DBDriver
     */
    private $db;
    private $prkey;
    protected $fields;

    public function __construct(SelectQuery $select, $prkey = "id", DBDriver $db = NULL)
    {

        $this->select = $select;

        $this->db = DBDriver::Get();

        $this->prkey = $prkey;
        $this->fields = $select->fields;

    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->db->free($this->res);
    }

    public function getBean()
    {
        return false;
    }

    public function getSelectQuery(SelectQuery $other = NULL)
    {
        return $this->select;
    }

    public function getSQL()
    {
        return $this->select->getSQL();
    }

    public function startQuery(SelectQuery $filter = NULL)
    {
        if (!$filter) $filter = $this->select;

        $sql = $filter->getSQL();
        $this->res = $this->db->query($sql);

        if (!$this->res) throw new Exception($this->db->getError() . "<br>$sql");

        //TODO:?
        $ret = $this->db->query("SELECT FOUND_ROWS() as total");
        $row = $this->db->fetch($ret);
        $total = (int)$row["total"];

        return $total;

    }

    public function haveMoreResults(&$row)
    {

        return ($row = $this->db->fetch($this->res));
    }

    public function key()
    {
        return $this->prkey;
    }

}

?>