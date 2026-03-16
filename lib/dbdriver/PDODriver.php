<?php
include_once("dbdriver/DBDriver.php");
include_once("dbdriver/PDOResult.php");

class PDODriver extends DBDriver
{
    private ?PDO $conn = null;

    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

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
            PDO::ATTR_EMULATE_PREPARES   => true,

            // turn off multi-statement
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,

            // unbuffered mode
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        );


        try {

            $this->conn = new PDO($dsn, $user, $pass, $options);

            //$this->conn->set_charset("utf8");
            //$this->conn->autocommit(FALSE);

//            $this->conn->exec("SET AUTOCOMMIT = 0");
//            $this->conn->exec("SET NAMES 'UTF8' COLLATE 'utf8_general_ci' ");
//            $this->conn->exec("SET collation_connection = 'utf8_general_ci' ");
//
//            $this->conn->exec("SET character_set_results = 'utf8'");
//            $this->conn->exec("SET character_set_connection = 'utf8'");
//            $this->conn->exec("SET character_set_client = 'utf8'");

            SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::OPENED));

        } catch (PDOException $e) {
            throw new Exception("PDO Connection Error: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->clearActiveResult();

        if ($this->conn instanceof PDO) {
            $this->conn = null; // Closing is in the destructor in PDO
            SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::CLOSED));
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
     * During nested queries app logic can decide to open additional connection to the DB server using DBConnections::CreateDriver()
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
            $sql = $statement->getSQL();
            //Debug::ErrorLog("Prepared SQL: " . $sql);
            $bindings = $statement->getBindings();
            //Debug::ErrorLog("Bindings: " , $bindings);

            $meta = $statement->getMeta();
            if ($meta) {
                Debug::ErrorLog("Executing [$meta] : ".$sql, $bindings);
            }

            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) throw new Exception("Prepare failed: ".$this->getError());

            if (!$stmt->execute($bindings)) throw new Exception("Execute failed: ".$this->getError());

            $result =  $this->statementResult($stmt);
//            if ($this->active) {
//                $this->active = Debug::Backtrace(-1) . $sql;
//            }
            return $result;

        }
        catch (Exception $e) {
            $message = "";
            if (isset($sql)) $message .= "SQL: ".$sql;
            if (isset($bindings)) $message .= " | Bindings: " . print_r($bindings, true);
            Debug::ErrorLog("Error: " . $e->getMessage() . " | " .$message);
            throw $e;
        }
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

    public function transaction(?string $name = null): void
    {
        //If we are already in transaction do nothing. PDO does not support multiple transactions
        if ($this->conn->inTransaction()) {
            Debug::ErrorLog("Already inside transaction.");
            return;
        }
        if (!$this->conn->beginTransaction()) throw new Exception("Starting transaction failed: ".$this->getError());
        Debug::ErrorLog("Beginning transaction.");
    }

    public function commit(?string $name = null): void
    {
        if (!$this->conn->inTransaction()) throw new Exception("Not in transaction: ".$this->getError());
        if (!$this->conn->commit()) throw new Exception("Commit failed: ".$this->getError());
        Debug::ErrorLog("Commited transaction.");
    }

    public function rollback(?string $name = null): void
    {
        if (!$this->conn->inTransaction()) throw new Exception("Not in transaction: ".$this->getError());
        if (!$this->conn->rollBack()) throw new Exception("Rollback failed: ".$this->getError());
        Debug::ErrorLog("Rolled back transaction.");
    }

    public function getError(): string
    {
        $info = $this->conn->errorInfo();
        return $info[2] ?? "";
    }
}