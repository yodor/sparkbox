<?php
include_once("iterators/IDataIterator.php");

class SQLQuery implements IDataIterator
{

    /**
     * @var SQLSelect
     */
    public $select;

    /**
     * @var DBDriver
     */
    protected $db;

    /**
     * Primary key for this iterator
     * @var string
     */
    protected $key;

    /**
     * Main table
     * @var string
     */
    protected $name;

    /**
     * DBDriver resource
     * @var null
     */
    protected $res;

    protected $numResults = -1;

    /**
     * Accessible bean
     * @var DBTableBean|null
     */
    protected $bean;

    public function __construct(SQLSelect $select, string $primaryKey = "id", string $tableName = "")
    {

        $this->select = $select;
        $this->key = $primaryKey;
        $this->name = $tableName;

        $this->db = DBConnections::Get();
        $this->bean = NULL;
    }

    public function __destruct()
    {
        $this->db->free($this->res);
    }

    public function __clone()
    {
        $this->select = clone $this->select;
    }

    public function exec(): int
    {
        $this->db->free($this->res);

        $this->res = $this->db->query($this->select->getSQL());

        if (!$this->res) {
            debug("Error: " . $this->db->getError() . " SQL: " . $this->select->getSQL());
            throw new Exception($this->db->getError());
        }

        $res = $this->db->query("SELECT FOUND_ROWS() as total");
        $row = $this->db->fetch($res);
        $this->numResults = (int)$row["total"];
        $this->db->free($res);

        return $this->numResults;
    }

    public function next()
    {
        if (!$this->res) throw new Exception("Not executed yet or no valid resource");

        $ret = $this->db->fetch($this->res);
        if (!$ret) {
            $this->db->free($this->res);
            $this->res = NULL;
        }
        return $ret;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
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