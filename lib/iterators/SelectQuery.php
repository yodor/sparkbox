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

    public function count() : int
    {
        return $this->countByCount();
    }

    /**
     * Returns the total number of results using a nested COUNT(*) query.
     * This approach is faster than SQL_CALC_FOUND_ROWS as it bypasses heavy columns and subqueries.
     * * @return int
     * @throws Exception
     */
    protected function countByCount(): int
    {
        if ($this->numResults !== -1) {
            return $this->numResults;
        }

        $start = microtime(true); // Start execution timer

        $driver = $this->assignDriver();

        // 1. Clone the original statement to preserve its state
        $innerStmt = clone $this->stmt;

        // 2. OPTIMIZATION: Clear all heavy columns from the INNER query.
        // Using a constant '1' prevents the execution of heavy subqueries (photos, attributes, etc.)
        // inside the VIEW or SELECT list, which are irrelevant for counting rows.
        $innerStmt->columns()->reset();
        $innerStmt->alias("1", "temp");

        // 3. Remove ORDER BY from the inner query as sorting is resource-intensive and unnecessary for counting
        $innerStmt->orderClear();

        // 4. Wrap the optimized inner statement as a Derived Table (subquery)
        // This ensures that GROUP BY or HAVING clauses in the original query remain functional.
        $derivedSelect = $innerStmt->getAsDerived("count_table");

        // 5. Prepare the OUTER query for the final aggregation
        $derivedSelect->columns()->reset();
        $derivedSelect->alias("COUNT(*)", "total_results");

        // 6. Clear LIMIT/OFFSET from the outer query to ensure it fetches the single aggregate result correctly
        $derivedSelect->limitClear();

        // Execute the optimized count query
        $result = $driver->query($derivedSelect);
        $data = $result->fetchResult();

        $this->numResults = (int)$data->get("total_results");
        $result->free();

        // Log performance data if the result set is large
        if ($this->numResults > 100) {
            $end = microtime(true);
            $executionTime = number_format($end - $start, 4);

            Debug::ErrorLog("--- COUNT(*) SQL: " . $derivedSelect->debugSQL());
            Debug::ErrorLog("--- COUNT(*) Result: " . $this->numResults);
            Debug::ErrorLog("[Count Check] Execution Time: {$executionTime}s");
        }

        return $this->numResults;
    }

    /**
     * Returns the total number of results during SELECT - (lazy initialization)
     * @return int
     * @throws Exception
     */
    protected function countByFoundRows(): int
    {
        // Return cached result if already calculated
        if ($this->numResults !== -1) return $this->numResults;

        $start = microtime(true); // Започваме отброяването

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

        if ($this->numResults>100) {
            $end = microtime(true);
            $executionTime = number_format($end - $start, 4);
            Debug::ErrorLog("--- SQL_CALC_FOUND_ROWS: ".$select->debugSQL());
            Debug::ErrorLog("--- SQL_CALC_FOUND_ROWS: " . $this->numResults);
            Debug::ErrorLog("[Count Check] Results: {$this->numResults} | Time: {$executionTime}s");
        }
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