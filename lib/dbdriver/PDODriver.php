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
        $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
//            PDO::MYSQL_ATTR_INIT_COMMAND => "SET AUTOCOMMIT = 0",
        ];

        try {
            $this->conn = new PDO($dsn, $user, $pass, $options);

            //$this->conn->set_charset("utf8");
            //$this->conn->autocommit(FALSE);

            $this->conn->exec("SET AUTOCOMMIT = 0");
            $this->conn->exec("SET NAMES 'UTF8' COLLATE 'utf8_general_ci' ");
            $this->conn->exec("SET collation_connection = 'utf8_general_ci' ");

            $this->conn->exec("SET character_set_results = 'utf8'");
            $this->conn->exec("SET character_set_connection = 'utf8'");
            $this->conn->exec("SET character_set_client = 'utf8'");

            Debug::ErrorLog("Opening PDO connection to database server");
            SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::OPENED));

        } catch (PDOException $e) {
            throw new Exception("PDO Connection Error: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        if (is_null($this->conn)) return;

        $this->conn = null; // В PDO затварянето става чрез унищожаване на обекта
        SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::CLOSED));
    }

    public function isConnected(): bool
    {
        return !is_null($this->conn);
    }

    // Inside PDODriver class
    protected int $lastAffectedRows = 0;

    public function query(string $str): true|DBResult
    {
        try {
            $stmt = $this->conn->query($str);
            if ($stmt === false) throw new Exception("Query failed");

            // Store affected rows for non-SELECT queries
            $this->lastAffectedRows = $stmt->rowCount();

            if ($stmt->columnCount() > 0) {
                return new PDOResult($stmt);
            }
            return true;
        } catch (PDOException $e) {
            $this->lastAffectedRows = 0;
            Debug::ErrorLog("PDO Query exception: " . $e->getMessage() . " | SQL: $str");
            throw new Exception("Query exception: " . $e->getMessage());
        }
    }

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
        return $this->conn->beginTransaction();
    }

    public function commit(?string $name = null): bool
    {
        return $this->conn->commit();
    }

    public function rollback(?string $name = null): bool
    {
        return $this->conn->rollback();
    }

    public function escape(string $data): string
    {
        return trim($this->conn->quote($data), "'");
    }

    // Помощни методи, които изисква DBDriver
    public function queryFields(string $table): true|DBResult
    {
        return $this->query("DESCRIBE $table");
    }

    public function tableExists(string $table): bool
    {
        try {
            $res = $this->query("SELECT 1 FROM `$table` LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function fieldType(string $table, string $field_name): string
    {
        // copy MySQLiDriver logic
        $result = $this->queryFields($table);
        while ($row = $result->fetch()) {
            if ($row["Field"] === $field_name) return $row["Type"];
        }
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