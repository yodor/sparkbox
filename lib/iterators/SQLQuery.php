<?php
include_once("iterators/IDataIterator.php");
include_once("dbdriver/IDBDriverAccess.php");
include_once("sql/SQLStatement.php");
include_once("sql/SQLSelect.php");

class SQLQuery implements IDataIterator, IDBDriverAccess
{

    /**
     * @var SQLSelect|null
     */
    public ?SQLStatement $select = null;

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

        $this->select = $select;
        $this->key = $primaryKey;
        $this->name = $tableName;

        $this->bean = NULL;
        $this->res = NULL;
        $this->db = DBConnections::Driver();
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
        $this->numResults = -1;

    }

    public function __clone()
    {
        $this->select = clone $this->select;
    }

    /**
     * Executes the provided or default statement.
     * Sets the internal statement pointer and fetches the DBResult.
     * @param SQLStatement|null $statement Optional external statement to execute
     * @throws Exception If database is not connected or query fails
     */
    public function exec(?SQLStatement $statement = null): void
    {
        if (!$this->db) throw new Exception("Database driver not initialized");

        $this->free();

        $driver = $this->db;

        if ($this->db->hasActiveStatement()) {
            Debug::ErrorLog("Connection is alread having active statement. Opening new connection ...");
            $driver = DBConnections::CreateDriver();
            //upgrade connection
            $this->db = $driver;
        }
        //clear cached count
        $this->numResults = -1;

        // Assign the statement to use (either passed or default from constructor)
        if (!is_null($statement)) $this->select = $statement;

        if (!($this->select instanceof SQLStatement)) throw new Exception("SQLStatement is not set");

        try {

            // Execute query in unbuffered mode
            $result = $driver->query($this->select);

            //we have result
            if ($result instanceof DBResult) {
                //Debug::ErrorLog("Setting result for fetching next");
                $this->res = $result;
            }

        } catch (Exception $e) {

            $this->free();

            Debug::ErrorLog("SQLQuery Execution Error: " . $e->getMessage());

            throw $e;
        }
    }

    /**
     *
     * @return array|null
     * @throws Exception
     */
    public function next() : ?array
    {
        if (!($this->res instanceof DBResult)) throw new Exception("Not executed yet or no valid result");

        $data = $this->res->fetch();

        if (is_array($data)) return $data;

        $this->free();

        return null;

    }

    /**
     * @return RawResult|null
     * @throws Exception
     */
    public function nextResult() : ?RawResult
    {
        if (!($this->res instanceof DBResult)) throw new Exception("Not executed yet or no valid result");

        $data = $this->res->fetchResult();
        if ($data instanceof RawResult) return $data;

        $this->free();

        return null;
    }

    /**
     * Current iterator is ready for fetching
     * @return bool
     */
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

    public function setDB(DBDriver $driver) : void
    {
        $this->db = $driver;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the total number of rows (lazy initialization) or affectedRows
     * @return int
     * @throws Exception
     */
    public function count(): int
    {
        Debug::ErrorLog("Executing count query");
        // Return cached result if already calculated
        if ($this->numResults !== -1) return $this->numResults;

        // Use SQL_CALC_FOUND_ROWS logic only for SELECT statements
        if ($this->select instanceof SQLSelect) {

            $driver = $this->db;

            if ($this->db->hasActiveStatement()) {
                Debug::ErrorLog("Connection is alread having active statement. Opening new connection ...");
                $driver = DBConnections::CreateDriver();
            }

            $select = clone $this->select;
            $select->setMode(SQLSelect::SQL_CALC_FOUND_ROWS);

            //do not reset the fields here as 'custom' columns might be used with grouping or having clauses
            //ie select (select field from table1) as custom_name from table2 having custom_name LIKE '%something%'
            //set limit to 0 as this is SQL_CALC_FOUND_ROWS we don't want any results in the buffer
            $select->limit = "0";

            $result = $driver->query($select);
            if (!($result instanceof DBResult)) {
                Debug::ErrorLog("Error executing SQL_CALC_FOUND_ROWS: " . $select->getSQL());
                throw new Exception("Unable to query SQL_CALC_FOUND_ROWS");
            }
            $result->free();

            //fetch the actual calculated number
            $result = $driver->query("SELECT FOUND_ROWS() as total_results LIMIT 1");
            if (!($result instanceof DBResult)) {
                Debug::ErrorLog("Error fetching FOUND_ROWS: " . $select->getSQL());
                throw new Exception("Unable to fetch FOUND_ROWS");
            }

            $this->numResults = $result->fetchResult()->get("total_results");
            $result->free();
            return $this->numResults;
        }
        else {
            if (!$this->isActive()) throw new Exception("Non-Select query needs to be executed first to return the affected row count");

            return $this->res->numRows();
        }
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