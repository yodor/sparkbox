<?php
include_once("lib/iterators/ISQLIterator.php");

class SQLQuery implements ISQLIterator
{

    /**
     * @var SQLSelect|null
     */
    public $select = null;

    /**
     * @var DBDriver
     */
    protected $db = null;
    protected $key = "";
    protected $res = null;

    public function __construct(SQLSelect $select, string $primaryKey = "id", DBDriver $db = NULL)
    {

        $this->select = $select;
        $this->key = $primaryKey;

        if ($db instanceof DBDriver) $this->db = $db;
        else $this->db = DBDriver::Get();

    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->db->free($this->res);
    }

    public function exec() : int
    {
        $this->db->free($this->res);

        $this->res = $this->db->query($this->select->getSQL());

        if (!$this->res) throw new Exception($this->db->getError());

        //TODO:?
        $res = $this->db->query("SELECT FOUND_ROWS() as total");
        $row = $this->db->fetch($res);
        $total = (int)$row["total"];
        $this->db->free($res);

        return $total;
    }

    public function next()
    {
        return $this->db->fetch($this->res);
    }

    public function key() : string
    {
        return $this->key;
    }

}

?>