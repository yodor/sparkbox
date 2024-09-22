<?php
include_once("iterators/IDataIterator.php");

class SQLQuery implements IDataIterator
{

    /**
     * @var SQLSelect
     */
    public SQLSelect $select;

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
     * @var DBResult|null
     */
    protected ?DBResult $res = null;

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
        $this->res = NULL;
    }

    public function __destruct()
    {
        $this->free();
    }

    public function free() : void
    {
        if ($this->res instanceof DBResult) {
            $this->res->free();
        }
        $this->res = NULL;
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
        if ($this->res instanceof DBResult) {
            $this->res->free();
        }

        //true or dbresult
        $this->res = $this->db->query($this->select->getSQL());

        $this->numResults = -1;

        if ($this->res instanceof DBResult) {
            $this->numResults = $this->res->numRows();
        }

        return $this->numResults;
    }

    /**
     * TODO: Deprecated use nextResult
     * @return array|null
     * @throws Exception
     */
    public function next() : ?array
    {
        if (!($this->res instanceof DBResult)) throw new Exception("Not executed yet or no valid result");

        $data = $this->res->fetch();
        if (is_array($data)) return $data;

        $this->res->free();
        $this->res = NULL;
        return null;

    }

    public function nextResult() : ?RawResult
    {
        if (!($this->res instanceof DBResult)) throw new Exception("Not executed yet or no valid result");

        $data = $this->res->fetchResult();
        if ($data instanceof RawResult) return $data;
        $this->res->free();
        $this->res = NULL;
        return null;
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