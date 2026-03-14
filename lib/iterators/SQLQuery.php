<?php
include_once("iterators/IDataIterator.php");
include_once("dbdriver/IDBDriverAccess.php");
include_once("storage/ICacheIdentifier.php");
include_once("sql/SQLStatement.php");

class SQLQuery implements IDataIterator, IDBDriverAccess, ICacheIdentifier
{

    /**
     * @var SQLStatement|null
     */
    public ?SQLStatement $stmt = null;

    /**
     * @var DBDriver|null
     */
    protected ?DBDriver $db = null;

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
     * Current result
     * @var DBResult|null
     */
    protected ?DBResult $res = null;

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

    public function __construct(?SQLSelect $select=null, string $primaryKey = "id", string $tableName = "")
    {
        $this->stmt = $select;
        $this->key = $primaryKey;
        $this->name = $tableName;

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
        $this->res = null;
        $this->numResults = -1;

    }

    public function __clone()
    {
        $this->stmt = clone $this->stmt;
    }

    /**
     * Proxy method for DBResult->affectedRows
     * @return int
     */
    public function affectedRows() : int
    {
        //select ?
        if (!is_null($this->res) && !$this->res->isActive()) {
            return $this->res->affectedRows();
        }
        return -1;
    }
    /**
     * Executes the provided or default statement.
     * Sets the internal statement pointer and fetches the DBResult.
     * @param SQLStatement|null $statement Optional external statement to execute
     * @throws Exception If database is not connected or query fails
     */
    public function exec(?SQLStatement $statement = null): void
    {
        //no driver set use the default global connection
        if (!$this->db) $this->db = DBConnections::Driver();

        $this->free();

        // Assign the statement to use (either passed or default from constructor)
        if (!is_null($statement)) $this->stmt = $statement;

        if (!($this->stmt instanceof SQLStatement)) throw new Exception("SQLStatement is null");

        //already active result-set for fetching
        if ($this->db->hasResultSet()) {
            //unbuffered mode handling
            Debug::ErrorLog("Current connection result-set is still active. Opening new connection ...");
            $this->db = DBConnections::CreateDriver($this->db->getConnectionName());
        }

        //clear cached count
        $this->numResults = -1;

        try {
            // Execute query in unbuffered mode
            $this->res = $this->db->query($this->stmt);

        } catch (Exception $e) {
            Debug::ErrorLog("Executing statement failed: " . $e->getMessage());
            $this->free();
            throw $e;
        }
    }

    /**
     * Return the result record data array or null if eof
     * Calls DBResult->free()
     *
     * @return array|null
     * @throws Exception
     */
    public function next() : ?array
    {
        if (!$this->isActive()) throw new Exception("No active result to fetch");

        $data = $this->res->fetch();
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

        $data = $this->res->fetchResult();
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
        return (!is_null($this->res) && $this->res->isActive());
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

    /**
     * Return the current DBDriver
     * @return DBDriver
     */
    public function getDB(): DBDriver
    {
        return $this->db;
    }

    public function setDB(DBDriver $driver) : void
    {
        $this->db = $driver;
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
        if (!($this->stmt instanceof SQLSelect)) {
            throw new Exception("Incorrect statement");
        }

        // Return cached result if already calculated
        if ($this->numResults !== -1) return $this->numResults;

        // Use SQL_CALC_FOUND_ROWS logic only for SELECT statements
        Debug::ErrorLog("Using SQL_CALC_FOUND_ROWS");

        $driver = $this->db ?? DBConnections::Driver();

        if ($driver->hasResultSet()) {
            Debug::ErrorLog("Current connection has active result-set. Creating temporary driver connection ...");
            $driver = DBConnections::CreateDriver();
        }

        $select = clone $this->stmt;
        $select->setMode(SQLSelect::SQL_CALC_FOUND_ROWS);

        //do not reset the fields here as 'custom' columns might be used with grouping or having clauses
        //ie select (select field from table1) as custom_name from table2 having custom_name LIKE '%something%'
        //set limit to 0 as this is SQL_CALC_FOUND_ROWS we don't want any results in the buffer
        $select->limit = "0";

        $result = $driver->query($select);
        $result->free();

        //fetch the actual calculated number
        $result = $driver->queryRaw("SELECT FOUND_ROWS() as total_results LIMIT 1");

        $this->numResults = $result->fetchResult()->get("total_results");
        $result->free();

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
        return $this->stmt->debugSQL();
    }
}