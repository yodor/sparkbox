<?php
include_once("db/DBQuery.php");
include_once("iterators/IDataIterator.php");
include_once("dbdriver/IDBDriverAccess.php");
include_once("storage/ICacheIdentifier.php");
include_once("sql/SQLStatement.php");

class SelectQuery extends DBQuery implements IDataIterator,  ICacheIdentifier
{

    /**
     * @var SQLSelect|null
     */
    public ?SQLSelect $stmt = null;

    /**
     * Primary key for this iterator
     * @var string
     */
    protected string $key = "";

    /**
     * Main table
     * @var string
     */
    protected string $name = "";

    /**
     * Only available after calling count()
     * @var int 
     */
    protected int $numResults = -1;

    /**
     * Accessible bean
     * @var DBTableBean|null
     */
    protected ?DBTableBean $bean = null;

    public function __construct(SQLSelect $select, string $primaryKey = "id", string $tableName = "")
    {
        parent::__construct();

        $this->stmt = $select;

        $this->key = $primaryKey;
        $this->name = $tableName;
    }

    /**
     * Called in start of exec and during DTOR
     * @return void
     */
    public function free() : void
    {
        parent::free();
        $this->numResults = -1;
    }

    public function exec(?SQLStatement $statement = null, ?DBDriver $db = null) : void
    {
        if (!is_null($statement)) throw new Exception("Can only exec SQLSelect from the constructor call.");
        //clear cached count
        $this->numResults = -1;
        parent::exec($this->stmt, $db);
    }

    /**
     * Return the result record data array or null if EOF calls DBResult->free()
     *
     * @return array|null
     * @throws Exception
     */
    public function next() : ?array
    {
        if (!$this->isActive()) throw new Exception("No active result to fetch");

        $data = $this->result->fetch();
        if (is_array($data)) return $data;

        $this->free();
        return null;
    }

    /**
     * Return the result record data as RawResult or null if eof
     * Calls DBResult->free()
     *
     * @return RawResult|null
     * @throws Exception
     */
    public function nextResult() : ?RawResult
    {
        if (!$this->isActive()) throw new Exception("No active result to fetch");

        $data = $this->result->fetchResult();
        if ($data instanceof RawResult) return $data;

        $this->free();
        return null;
    }

    /**
     * Proxy method for DBResult->isActive() - Current result has records to fetch
     * INSERT/UPDATE/DELETE return false here
     * @return bool
     */
    public function isActive() : bool
    {
        return (!is_null($this->result) && $this->result->isActive());
    }

    /**
     * Return the accessible key name
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Set the accessible key name ie bean->key()
     * @param string $key
     * @return void
     */
    public function setKey(string $key) : void
    {
        $this->key = $key;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the total number of results during SELECT - (lazy initialization)
     * @return int
     * @throws Exception
     */
    public function count(): int
    {
        // Return cached result if already calculated
        if ($this->numResults !== -1) return $this->numResults;

        //get suitable driver
        //if new driver was created it will get out of context and auto deleted
        $driver = $this->assignDriver();

        $select = clone $this->stmt;
        $select->setMode(SQLSelect::SQL_CALC_FOUND_ROWS);

        //do not reset the fields here as 'custom' columns might be used with grouping or having clauses
        //ie select (select field from table1) as custom_name from table2 having custom_name LIKE '%something%'
        //set limit to 0 as this is SQL_CALC_FOUND_ROWS we don't want any results in the buffer
        $select->limit(0);

        $result = $driver->query($select);
        $result->free();

        //fetch the actual calculated number
        $result = $driver->query(new RawSQLSelect("SELECT FOUND_ROWS() as total_results LIMIT 1"));

        $this->numResults = $result->fetchResult()->get("total_results");
        $result->free();

//        Debug::ErrorLog("SQL_CALC_FOUND_ROWS: ".$this->numResults);

        return $this->numResults;
    }

    /**
     * Set accessible/related bean
     * @param DBTableBean $bean
     * @return void
     */
    public function setBean(DBTableBean $bean) : void
    {
        $this->bean = $bean;
    }

    /**
     * Get accessible/related bean
     * @return DBTableBean|null
     */
    public function bean(): ?DBTableBean
    {
        return $this->bean;
    }

    public function getCacheName() : string
    {
        return Spark::Hash($this->stmt->debugSQL());
    }

}