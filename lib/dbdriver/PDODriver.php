<?php
include_once("dbdriver/DBDriver.php");
include_once("dbdriver/PDOResult.php");

class PDODriver extends DBDriver
{
    private ?PDO $conn = null;


    /**
     * Open connection to DB server
     * @param bool $persistent
     * @return void
     * @throws Exception
     */
    public function connect(bool $persistent=false): void
    {
        if ($this->isConnected()) {
            return;
        }

        // Retain persistent choice on the driver instance
        $this->persistent = $persistent;

        $host = $this->props->host;
        $db   = $this->props->database;
        $port = $this->props->port;
        $user = $this->props->user;
        $pass = $this->props->pass;

        // Construct DSN (Data Source Name)  //charset=utf8mb4
        $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4";

        $options = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            //allow autocommit control with beginTransaction
            PDO::ATTR_AUTOCOMMIT           => true,

            // allow reusing of named parameters
            //PDO::ATTR_EMULATE_PREPARES   => true,

            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,

            // prefer persistent during storage asset requests
            PDO::ATTR_PERSISTENT         => $persistent,
        );

        $my = class_exists(\Pdo\Mysql::class);
        // turn off multi-statement
        $options[$my ? \Pdo\Mysql::ATTR_MULTI_STATEMENTS : PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
        // unbuffered mode
        $options[$my ? \Pdo\Mysql::ATTR_USE_BUFFERED_QUERY : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;


        try {

            $this->conn = new PDO($dsn, $user, $pass, $options);
            $this->txDepth = 0;

            $this->applySessionVariables();

            SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::OPENED, $this));

        } catch (PDOException $e) {
            throw new Exception("PDO Connection Error: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->clearActiveResult();

        if ($this->conn instanceof PDO && $this->txDepth > 0) {
            Debug::ErrorLog("Disconnect with open transaction (depth {$this->txDepth}) - rolling back");
            try {
                if ($this->conn->inTransaction()) $this->conn->rollBack();
            }
            catch (PDOException $e) {
                Debug::ErrorLog("Rollback on disconnect failed: " . $e->getMessage());
            }
            $this->txDepth = 0;
            $this->commitCallbacks = array();
        }

        if ($this->conn instanceof PDO) {
            $this->conn = null; // Closing is in the destructor in PDO
            SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::CLOSED, $this));
        }
    }

    public function isConnected(): bool
    {
        return !is_null($this->conn);
    }

    // Inside PDODriver class
    protected int $lastAffectedRows = -1;

    protected ?PDOResult $active = null;
    /**
     *
     * @param PDOStatement $stmt
     * @return PDOResult
     */
    protected function statementResult(PDOStatement $stmt): PDOResult
    {

        $result = new PDOResult($stmt);

        // This is a SELECT / SHOW / EXPLAIN / DESCRIBE / CALL returning rows
        // Most reliable way to check for result set
        if ($result->isActive()) {
            //result set number of rows is not known
            $this->lastAffectedRows = -1;
            //only one active in unbuffered mode
            $this->active = $result;

        }
        else {
            // This is INSERT / UPDATE / DELETE / REPLACE / CREATE / DROP / etc.
            $this->lastAffectedRows = $result->affectedRows();
            // or get last insert id: $pdo->lastInsertId()
            //do not track as current but PDOResult->affectedRows() can still be used as 'affected rows' from the rest of the app
        }

        return $result;
    }

    private function clearActiveResult() : void
    {
        if ($this->active) {
            //Debug::ErrorLog("This is NOT SELECT QUERY clearing current");
            $this->active->free();
            $this->active = null;
        }
    }
    /**
     * Do we have un-fetched result-set waiting ?
     * During nested queries app logic can decide to open additional connection to the DB server using DBManager::CreateDriver()
     * @return bool
     */
    public function hasActiveResult() : bool
    {
        return (!is_null($this->active) && $this->active->isActive());
    }

    /**
     * Throw if we have non fully fetched result set
     * clear any active statement set from previous call to statementResult()
     * @return void
     * @throws Exception
     */
    protected function assert_active() : void
    {
        if ($this->hasActiveResult()) {
            throw new Exception("Fetch active result-set first");
        }

        $this->clearActiveResult();
    }

    /**
     * Executes a prepared statement using SQLStatement object
     * @param SQLStatement $statement
     * @return PDOResult
     * @throws Exception
     */
    public function query(SQLStatement $statement): PDOResult
    {
        $this->lastAffectedRows = -1;

        $this->assert_active();

        try {

            $meta = $statement->getMeta();
            if ($meta) {
                Debug::ErrorLog("Executing [$meta] : ".$statement->debugSQL());
            }

            $stmt = $this->conn->prepare($statement->getSQL());
            if ($stmt === false) throw new Exception("Prepare failed: ".$this->getError());

            foreach ($statement->getBindings() as $key => $value) {
                $stmt->bindValue($key, $value, PDODriver::BindingType($value));
            }

            if (!$stmt->execute()) throw new Exception("Execute failed: ".$this->getError());

            $result =  $this->statementResult($stmt);
//            if ($this->active) {
//                $this->active = Debug::Backtrace(-1) . $sql;
//            }
            return $result;

        }
        catch (Exception $e) {
            Debug::ErrorLog("Query Failed: " . $e->getMessage() . " | " .$statement->debugSQL());
            throw $e;
        }
    }

    /**
     * Very simple but effective type mapper
     */
    private static function BindingType(string|float|int|bool|null $value): int
    {
        return match (true) {
            is_null($value)  => PDO::PARAM_NULL,
            is_bool($value)  => PDO::PARAM_BOOL,
            is_int($value)   => PDO::PARAM_INT,
            default          => PDO::PARAM_STR,
        };
    }

    /**
     * Returns the number of rows affected by the last query
     */
    public function affectedRows(): int
    {
        return $this->lastAffectedRows;
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     * Proxy method to $this->conn->lastInsertId()
     * @return int
     */
    public function lastID(): int
    {
        return (int)$this->conn->lastInsertId();
    }

    /**
     * Current transaction nesting depth. 0 = not in transaction, 1 = top level, >1 = savepoints
     * @var int
     */
    protected int $txDepth = 0;

    public function inTransaction(): bool
    {
        return $this->txDepth > 0;
    }

    /**
     * Callbacks to run after the outermost commit, indexed by the transaction depth they were registered at
     * @var array<int, array<Closure>>
     */
    protected array $commitCallbacks = array();

    public function onCommit(Closure $callback): void
    {
        if ($this->txDepth === 0) {
            $callback();
            return;
        }
        $this->commitCallbacks[$this->txDepth][] = $callback;
    }

    /**
     * Run and clear all queued commit callbacks. A failing callback is logged, it can not undo the commit.
     * @return void
     */
    protected function runCommitCallbacks(): void
    {
        $queued = $this->commitCallbacks;
        $this->commitCallbacks = array();
        ksort($queued);
        foreach ($queued as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $callback();
                }
                catch (Throwable $e) {
                    Debug::ErrorLog("onCommit callback failed: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Begin a transaction. Nested calls create a SAVEPOINT, so inner code can commit/rollback
     * its own part without ending the outer transaction.
     * @param string|null $name unused - savepoint names are generated from the depth
     * @throws Exception
     */
    public function transaction(?string $name = null): void
    {
        $this->assert_active();

        if ($this->txDepth === 0) {
            if (!$this->conn->beginTransaction()) throw new Exception("Starting transaction failed: ".$this->getError());
            Debug::ErrorLog("Beginning transaction.");
        }
        else {
            $this->conn->exec("SAVEPOINT sp{$this->txDepth}");
            Debug::ErrorLog("Nested transaction - SAVEPOINT sp{$this->txDepth}");
        }
        $this->txDepth++;
    }

    /**
     * Commit the innermost transaction level. Only the outermost commit really commits.
     * @throws Exception
     */
    public function commit(?string $name = null): void
    {
        if ($this->txDepth === 0) throw new Exception("Commit without transaction");

        $this->assert_active();

        $level = $this->txDepth;
        $this->txDepth--;

        if ($this->txDepth > 0) {
            $this->conn->exec("RELEASE SAVEPOINT sp{$this->txDepth}");
            Debug::ErrorLog("Released SAVEPOINT sp{$this->txDepth}");
            //released savepoint - its callbacks now belong to the enclosing level
            if (isset($this->commitCallbacks[$level])) {
                $this->commitCallbacks[$this->txDepth] = array_merge($this->commitCallbacks[$this->txDepth] ?? array(), $this->commitCallbacks[$level]);
                unset($this->commitCallbacks[$level]);
            }
            return;
        }

        //DDL statements (CREATE/ALTER/DROP ...) commit implicitly on the server side
        if (!$this->conn->inTransaction()) {
            $this->commitCallbacks = array();
            throw new Exception("Transaction was ended implicitly by the server before commit");
        }
        if (!$this->conn->commit()) {
            $this->commitCallbacks = array();
            throw new Exception("Commit failed: ".$this->getError());
        }
        Debug::ErrorLog("Commited transaction.");

        $this->runCommitCallbacks();
    }

    /**
     * Roll back the innermost transaction level.
     * Never throws when there is nothing to roll back - it is called from catch blocks and
     * must not replace the original exception.
     */
    public function rollback(?string $name = null): void
    {
        if ($this->txDepth === 0) {
            Debug::ErrorLog("Rollback called without transaction - ignored");
            return;
        }

        //an unfetched result blocks the connection - discard it, we are aborting anyway
        $this->clearActiveResult();

        //drop callbacks registered at this level (whole transaction when the level is 1)
        $level = $this->txDepth;
        foreach (array_keys($this->commitCallbacks) as $key) {
            if ($key >= $level) unset($this->commitCallbacks[$key]);
        }

        $this->txDepth--;

        try {
            if ($this->txDepth > 0) {
                $this->conn->exec("ROLLBACK TO SAVEPOINT sp{$this->txDepth}");
                Debug::ErrorLog("Rolled back to SAVEPOINT sp{$this->txDepth}");
            }
            else if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
                Debug::ErrorLog("Rolled back transaction.");
            }
        }
        catch (PDOException $e) {
            Debug::ErrorLog("Rollback failed: " . $e->getMessage());
        }
    }

    /**
     * Sync the session time_zone with PHP date.timezone, then apply DBConfig::getVariables()
     *
     * TIMESTAMP columns are stored in UTC and converted to/from text using the session time_zone,
     * the app converts that text back with strtotime() using date.timezone - both must be the same
     * DST-aware zone. Named zones need the server tz tables (mysql_tzinfo_to_sql). If they are
     * missing the server default (usually SYSTEM) is kept as before and a warning is logged.
     * @throws Exception
     */
    protected function applySessionVariables(): void
    {
        $zone = date_default_timezone_get();
        try {
            $this->conn->exec("SET SESSION time_zone = " . $this->conn->quote($zone));
        }
        catch (PDOException $e) {
            Debug::ErrorLog("WARNING: cannot set session time_zone '$zone' (load tz tables with mysql_tzinfo_to_sql) - keeping server default: " . $e->getMessage());
        }

        $sets = array();
        foreach ($this->props->getVariables() as $name => $value) {
            if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $name)) throw new Exception("Invalid session variable name: $name");
            $sets[] = "SESSION $name = " . (is_int($value) ? $value : $this->conn->quote((string)$value));
        }
        if (count($sets) > 0) {
            $this->conn->exec("SET " . implode(", ", $sets));
        }
    }

    public function getError(): string
    {
        $info = $this->conn->errorInfo();
        return $info[2] ?? "";
    }
}