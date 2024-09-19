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
    protected DBDriver $db;

    /**
     * Primary key for this iterator
     * @var string
     */
    protected string $key;

    /**
     * Main table
     * @var string
     */
    protected string $name;

    /**
     * DBDriver resource
     * @var null
     */
    protected $res;

    protected int $numResults = -1;

    /**
     * Accessible bean
     * @var DBTableBean|null
     */
    protected ?DBTableBean $bean = null;

    public function __construct(SQLSelect $select, string $primaryKey = "id", string $tableName = "")
    {

        $this->select = $select;
        $this->key = $primaryKey;
        $this->name = $tableName;

        $this->db = DBConnections::Open();
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

    /**
     * Execute the query and return the number of result rows
     * @return int Number of result rows
     * @throws Exception
     */
    public function exec(): int
    {
        $this->db->free($this->res);

        $this->res = $this->db->query($this->select->getSQL());

        if (!$this->res) {
            debug("Error: " . $this->db->getError() . " SQL: " . $this->select->getSQL());
            throw new Exception($this->db->getError());
        }

        $this->numResults = $this->db->numRows($this->res);

        return $this->numResults;
    }

    /**
     * TODO: Deprecated use nextResult
     * @return array|null
     * @throws Exception
     */
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

    public function nextResult() : ?RawResult
    {
        if (!$this->res) throw new Exception("Not executed yet or no valid resource");

        $ret = $this->db->fetchResult($this->res);
        if (!$ret) {
            $this->db->free($this->res);
            $this->res = NULL;
        }
        return $ret;
    }

    public function free()
    {
        $this->db->free($this->res);
        $this->res = NULL;
    }

    public function isActive() : bool
    {
        return (!is_null($this->res));
    }

    public function key(): string
    {
        return $this->key;
    }

    public function setKey(string $key) : void
    {
        $this->key = $key;
    }

    public function getDB(): DBDriver
    {
        return $this->db;
    }

    public function setDB(DBDriver $db) : void
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

    public function setBean(DBTableBean $bean) : void
    {
        $this->bean = $bean;
    }

    public function bean(): ?DBTableBean
    {
        return $this->bean;
    }
}

?>