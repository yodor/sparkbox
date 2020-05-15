<?php
include_once("iterators/IDataIterator.php");

class SQLQuery implements IDataIterator
{

    /**
     * @var SQLSelect|null
     */
    public $select = NULL;

    /**
     * @var DBDriver
     */
    protected $db = NULL;
    protected $key = "";
    protected $name = "";

    protected $res = NULL;

    protected $numResults = 0;

    public function __construct(SQLSelect $select, string $primaryKey = "id", string $tableName = "")
    {

        $this->select = $select;
        $this->key = $primaryKey;
        $this->name = $tableName;

        $this->db = DBConnections::Get();

    }

    public function __destruct()
    {
        $this->db->free($this->res);
    }

    public function exec(): int
    {
        $this->db->free($this->res);

        $this->res = $this->db->query($this->select->getSQL());

        if (!$this->res) {
            debug("Error: " . $this->db->getError() . " SQL: " . $this->select->getSQL());
            throw new Exception($this->db->getError());
        }

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

    public function key(): string
    {
        return $this->key;
    }

    public function getDB(): DBDriver
    {
        return $this->db;
    }

    public function setDB(DBDriver $db)
    {
        $this->db = $db;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function count(): int
    {
        return $this->numResults;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
    }

    public function bean(): ?DBTableBean
    {
        return $this->bean;
    }
}

?>