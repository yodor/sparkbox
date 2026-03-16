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
            // allow reusing of named parameters
            PDO::ATTR_EMULATE_PREPARES   => true,

            // turn off multi-statement
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,

            // unbuffered mode
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,

            PDO::MYSQL_ATTR_INIT_COMMAND => "SET AUTOCOMMIT=0",
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
        if ($this->current instanceof PDOResult) {
            $this->current->free();
            $this->current=null;
        }

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

    protected ?PDOResult $current = null;
    /**
     *
     * @param PDOStatement $stmt
     * @return PDOResult
     */
    protected function statementResult(PDOStatement $stmt): PDOResult
    {

        $result = new PDOResult($stmt);

        // Most reliable way to check for result set
        if ($stmt->columnCount() > 0) {
            // This is a SELECT / SHOW / EXPLAIN / DESCRIBE / CALL returning rows
            //result set number of rows is not known
            $this->lastAffectedRows = -1;

            //only one active in unbuffered mode
            $this->current = $result;

        } else {

            $this->current = null;
            // This is INSERT / UPDATE / DELETE / REPLACE / CREATE / DROP / etc.
            $this->lastAffectedRows = $stmt->rowCount();
            // or get last insert id: $pdo->lastInsertId()
            //do not track as current but PDOResult->numRows() can still be used as 'affected rows' from the rest of the app
        }

        return $result;
    }

    /**
     * Do we have un-fetched result-set waiting ?
     * During nested queries app logic can decide to open additional connection to the DB server using DBConnections::CreateDriver()
     * @return bool
     */
    public function hasResultSet() : bool
    {
        return (!is_null($this->current) && $this->current->isActive());
    }

    /**
     * Throw if we have non fully fetched result set
     * @return void
     * @throws Exception
     */
    protected function assertResultSet() : void
    {
        if ($this->hasResultSet())
            throw new Exception("Fetch active result-set first: ".Debug::Backtrace(-1)." | Active statement from: ".$this->current->createdBy);
    }

    public function queryRaw(string $sqlText): PDOResult
    {
        $this->lastAffectedRows = -1;

        $this->assertResultSet();

        try {
            $stmt = $this->conn->query($sqlText);
            if ($stmt === false) throw new Exception("Query failed: ".$this->getError());

            return $this->statementResult($stmt);
        }
        catch (Exception $e) {
            Debug::ErrorLog("Error: " . $e->getMessage() . " | SQL: " . ($sqlText ?? ""));
            throw $e;
        }
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

        $this->assertResultSet();

        try {
            $sql = $statement->getSQL();
            //Debug::ErrorLog("Prepared SQL: " . $sql);
            $bindings = $statement->getBindings();
            //Debug::ErrorLog("Bindings: " , $bindings);

            $meta = $statement->getMeta();
            if ($meta) {
                Debug::ErrorLog("Executing query[$meta] : ".$sql, $bindings);
            }

            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) throw new Exception("Prepare failed: ".$this->getError());
            if (!$stmt->execute($bindings)) throw new Exception("Execute failed: ".$this->getError());

            return $this->statementResult($stmt);
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

    public function lastID(): int
    {
        return (int)$this->conn->lastInsertId();
    }

    public function transaction(?string $name = null): bool
    {
        $this->connect();
        //If we are already in transaction do nothing. PDO does not support multiple transactions
        if ($this->conn->inTransaction()) {
            return true;
        }
        return $this->conn->beginTransaction();
    }

    public function commit(?string $name = null): bool
    {
        if ($this->isConnected() && $this->conn->inTransaction()) {
            return $this->conn->commit();
        }
        return false;
    }

    public function rollback(?string $name = null): bool
    {
        if ($this->isConnected() && $this->conn->inTransaction()) {
            return $this->conn->rollBack();
        }
        return false;
    }

    public function columnTypes(string $tableName): array
    {
        $types = array();
        $result = $this->queryRaw("DESCRIBE $tableName");
        while ($data = $result->fetch()) {
            $columnName = $data["Field"];
            $types[$columnName] = $data;
        }
        return $types;
    }

    public function tableExists(string $tableName): bool
    {
        try {
            $result = $this->queryRaw("SELECT 1 FROM `{$tableName}` LIMIT 1");
            $result->free();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getError(): string
    {
        $info = $this->conn->errorInfo();
        return $info[2] ?? "";
    }
}