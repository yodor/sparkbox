<?php
include_once("dbdriver/DBDriver.php");
include_once("dbdriver/MySQLiResult.php");

class MySQLiDriver extends DBDriver
{
    /**
     * @var mysqli|null
     */
    private ?mysqli $conn = null;


    /**
     * @param DBConnection $props
     * @throws Exception
     */
    public function __construct(DBConnection $props)
    {

        $host = $props->host;

        if ($props->isPersistent()) {
            $host = "p:" . $host;
        }

        $this->conn = @new mysqli($host, $props->user, $props->pass, $props->database, $props->port);

        if ($this->conn->connect_errno) {
            throw new Exception("Error connecting to database server: " . $this->conn->connect_error);
        }

        $this->conn->autocommit(FALSE);
        $this->conn->set_charset("utf8");

        $this->conn->query("SET NAMES 'UTF8' COLLATE 'utf8_general_ci' ");
        $this->conn->query("SET collation_connection = 'utf8_general_ci' ");

        $this->conn->query("SET character_set_results = 'utf8'");
        $this->conn->query("SET character_set_connection = 'utf8'");
        $this->conn->query("SET character_set_client = 'utf8'");

        SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::OPENED));
    }

    public function __destruct()
    {
        if ($this->conn instanceof mysqli) {
            $this->conn->close();
            SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::CLOSED));
        }
    }

    public function connection(): mysqli
    {
        return $this->conn;
    }

    public function getError(): string
    {
        return $this->conn->error;
    }

    public function dateTime(int $add_days = 0, $interval_type = " DAY ") : string
    {
        $result = $this->query("SELECT DATE_ADD(now(), INTERVAL $add_days $interval_type) as datetime");
        if (!($result instanceof DBResult)) throw new Exception("Unable to query dateTime: ".$this->getError());
        $row = $result->fetch();
        return $row["datetime"];
    }

    public function timestamp() : int
    {
        $result = $this->query("SELECT UNIX_TIMESTAMP(CURRENT_TIMESTAMP) as datetime");
        if (!($result instanceof DBResult)) throw new Exception("Unable to query timestamp: ".$this->getError());
        $row = $result->fetch();
        return intval($row["datetime"]);
    }

    /**
     * For successful queries which produce a result set, such as SELECT, SHOW, DESCRIBE or EXPLAIN,
     * mysqli_query will return a mysqli_result object.
     * For other successful queries mysqli_query will return true else throws exception
     * @param string $str
     * @return true|DBResult
     * @throws Exception
     */
    public function query(string $str) : true|DBResult
    {

        try {
            $res = $this->conn->query($str);
            if ($res === false) throw new Exception("Result is false");
        }
        catch (Exception $e) {
            Debug::ErrorLog("Query exception: ".$e->getMessage()." | Connection Error: ".$this->conn->error." | SQL: $str");
            throw new Exception("Query exception: ".$e->getMessage());
        }

        if ($res instanceof mysqli_result) return new MySQLiResult($res);

        return true;
    }

    public function affectedRows(): int
    {
        return $this->conn->affected_rows;
    }

    public function lastID(): int
    {
        return $this->conn->insert_id;
    }

    public function commit(?string $name = null) : bool
    {
        return $this->conn->commit(0 , $name);
    }

    public function rollback(?string $name = null) : bool
    {
        return $this->conn->rollback(0 , $name);
    }

    public function transaction(?string $name = null) : bool
    {
        return $this->conn->begin_transaction(0, $name);
    }

    public function escape(string $data) : string
    {
        return $this->conn->real_escape_string($data);
    }

    public function queryFields(string $table) : true|DBResult
    {
        return $this->query("show fields from $table");
    }

    public function fieldType(string $table, string $field_name) : string
    {
        $ret = "";
        $found = FALSE;
        $result = $this->queryFields($table);
        while ($row = $result->fetch()) {
            if (strcmp($row["Field"], $field_name) != 0) continue;
            $ret = $row["Type"];
            $found = TRUE;
            break;
        }
        if (!$found) throw new Exception("Field [$field_name] does not exist in table: $table");
        return $ret;
    }

    public function tableExists(string $table) : bool
    {
        $res = $this->query("show tables like '$table'");
        if ($res->numRows() < 1) return FALSE;
        return TRUE;
    }

}