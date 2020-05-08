<?php
include_once("iterators/IDataIterator.php");

class SQLQuery implements IDataIterator
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
    protected $name = "";

    protected $res = null;

    protected $numResults = 0;

    public function __construct(SQLSelect $select, string $primaryKey = "id", string $tableName="")
    {

        $this->select = $select;
        $this->key = $primaryKey;
        $this->name = $tableName;

        $this->db = DBDriver::Get();

    }

    public function __destruct()
    {
        $this->db->free($this->res);
    }

    public function exec() : int
    {
        $this->db->free($this->res);

        $this->res = $this->db->query($this->select->getSQL());
       // mysqli_result::fetch_fields
        if (!$this->res) throw new Exception($this->db->getError());

        //TODO:?
        $res = $this->db->query("SELECT FOUND_ROWS() as total");
        $row = $this->db->fetch($res);
        $this->numResults = (int)$row["total"];
        $this->db->free($res);

        return $this->numResults;
    }

    public function next()
    {
        if (!$this->res) throw new Exception("Not executed yet");
        
        $ret = $this->db->fetch($this->res);
        if (!$ret) $this->db->free($this->res);
        return $ret;
    }

    public function key() : string
    {
        return $this->key;
    }

    public function getDB() : DBDriver
    {
        return $this->db;
    }

    public function setDB(DBDriver $db)
    {
        $this->db = $db;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function count() : int
    {
        return $this->numResults;
    }
}

?>