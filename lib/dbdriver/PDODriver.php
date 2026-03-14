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
            PDO\MYSQL::ATTR_MULTI_STATEMENTS => false,

            // unbuffered mode
            PDO\MYSQL::ATTR_USE_BUFFERED_QUERY => false,

            PDO\MYSQL::ATTR_INIT_COMMAND        => "SET AUTOCOMMIT=0",
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

            Debug::ErrorLog("Opening PDO connection to database server");
            SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::OPENED));

        } catch (PDOException $e) {
            throw new Exception("PDO Connection Error: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        if ($this->current) {
            $this->current->free();
            $this->current=null;
        }
        if (is_null($this->conn)) return;
        $this->conn = null; // Closing is in the destructor in PDO
        Debug::ErrorLog("Closing PDO connection");
        SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::CLOSED));
    }

    public function isConnected(): bool
    {
        return !is_null($this->conn);
    }

    // Inside PDODriver class
    protected int $lastAffectedRows = 0;

    /**
     * Main query entry point.
     * @param SQLStatement|string $statement
     * @return PDOResult
     * @throws Exception
     */
    public function query(SQLStatement|string $statement): PDOResult
    {
        $this->lastAffectedRows = 0;

        if ($statement instanceof SQLStatement) {
            return $this->queryPrepared($statement);
        }
        return $this->queryRaw($statement);
    }

    protected ?PDOResult $current = null;
    /**
     *
     * @param PDOStatement $stmt
     * @return PDOResult
     */
    protected function statementResult(PDOStatement $stmt): PDOResult
    {
        if (!is_null($this->current)) {
            throw new Exception("Active statement found created by: ".$this->current->createdBy);
        }

        // If the query returns data (SELECT, DESCRIBE, etc.)
        if ($stmt->columnCount() > 0) {
            // Critical: Store affected rows immediately after execution
            $this->lastAffectedRows = $stmt->rowCount();
        }

        $this->current = new PDOResult($stmt);
        return $this->current;
    }

    /**
     * Current connection is active. Open new one ?
     * @return bool
     */
    public function hasActiveStatement() : bool
    {
        return !is_null($this->current) && $this->current->isActive();
    }

    protected function assert_current() : void
    {
        if (!is_null($this->current)) {
            if ($this->current->isActive()) {
                throw new Exception("StackTrace: ".Debug::Backtrace(-1)." | Active statement from: ".$this->current->createdBy);
            }
            else {
                //free current instance
                $this->current = null;
            }
        }
    }

    protected function queryRaw(string $sql): PDOResult
    {
        try {
            $this->assert_current();

            $stmt = $this->conn->query($sql);
            if ($stmt === false) throw new Exception("query failed");

            return $this->statementResult($stmt);
        }
        catch (Exception $e) {
            Debug::ErrorLog("Error: " . $e->getMessage() . " | SQL: " . ($sql ?? ""));
            throw new Exception("Error: " . $e->getMessage());
        }
    }

    /**
     * Executes a prepared statement using SQLStatement object
     * @param SQLStatement $statement
     * @return PDOResult
     * @throws Exception
     */
    protected function queryPrepared(SQLStatement $statement): PDOResult
    {

        try {
            $this->assert_current();

            $sql = $statement->getPreparedSQL();
            Debug::ErrorLog("Prepared SQL: " . $sql);
            $bindings = $statement->getBindings();
            Debug::ErrorLog("Bindings: " , $bindings);

            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) throw new Exception("prepare failed");
            if (!$stmt->execute($bindings)) throw new Exception("execute failed");

            return $this->statementResult($stmt);
        }
        catch (Exception $e) {
            $message = "";
            if (isset($sql)) $message .= "SQL: ".$sql;
            if (isset($bindings)) $message .= " | Bindings: " . print_r($bindings, true);
            Debug::ErrorLog("Error: " . $e->getMessage() . " | " .$message);
            throw new Exception("Error: " . $e->getMessage());
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

    public function escape(string $data): string
    {
        return trim($this->conn->quote($data), "'");
    }

    // Помощни методи, които изисква DBDriver
    public function queryFields(string $table): DBResult
    {
        return $this->query("DESCRIBE $table");
    }

    public function tableExists(string $table): bool
    {
        try {
            $result = $this->query("SELECT 1 FROM `$table` LIMIT 1");
            $result->free();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function fieldType(string $table, string $field_name): string
    {
        // copy MySQLiDriver logic
        $result = $this->queryFields($table);
        $ret = "";
        while ($row = $result->fetch()) {
            if ($row["Field"] === $field_name) {
                $ret = $row["Type"];
                break;
            }
        }
        $result->free();
        if ($ret) return $ret;
        throw new Exception("Field [$field_name] does not exist in table: $table");

    }

    public function dateTime(int $add_days = 0, string $interval_type = " DAY "): string
    {
        $result = $this->query("SELECT DATE_ADD(now(), INTERVAL $add_days $interval_type) as datetime");
        $row = $result->fetch();
        return $row["datetime"];
    }

    public function timestamp(): int
    {
        $result = $this->query("SELECT UNIX_TIMESTAMP(CURRENT_TIMESTAMP) as dt");
        $row = $result->fetch();
        return (int)$row["dt"];
    }

    public function getError(): string
    {
        $info = $this->conn->errorInfo();
        return $info[2] ?? "";
    }
}