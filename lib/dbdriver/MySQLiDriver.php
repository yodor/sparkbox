<?php
include_once("dbdriver/DBDriver.php");

class MySQLiDriver extends DBDriver
{
    /**
     * @var mysqli
     */
    private mysqli $conn;


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
        $res = $this->query("SELECT DATE_ADD(now(), INTERVAL $add_days $interval_type) as datetime");
        $row = $this->fetch($res);
        return $row["datetime"];
    }

    public function timestamp() : int
    {
        $res = $this->query("SELECT UNIX_TIMESTAMP(CURRENT_TIMESTAMP) as datetime");
        $row = $this->fetch($res);
        return intval($row["datetime"]);
    }

    public function query(string $str)
    {
        //debug("Excuting SQL: ".$str);
        return $this->conn->query($str);
    }

    public function affectedRows(): int
    {
        return $this->conn->affected_rows;
    }

    public function numRows($result): int
    {
        $result = $this->assert_resource($result);
        return $result->num_rows;
    }

    public function fields($result) : array
    {
        $result = $this->assert_resource($result);
        return $result->fetch_fields();
    }

    protected function assert_resource($res): mysqli_result
    {
        if (!($res instanceof mysqli_result)) throw new Exception("No valid mysqli_resource passed");
        return $res;
    }

    /**
     * Fetch the next row of the result set $result as associative array
     * @param $result mysqli_result
     * @return array|null Associative array of the current result record or null if there are no more records
     * @throws Exception
     */
    public function fetch($result): ?array
    {
        $result = $this->assert_resource($result);

        //null indicates no more records from this resource
        $record = $result->fetch_array(MYSQLI_ASSOC);

        if ($record === false) throw new Exception("Error fetching the result: ".$this->getError());

        return $record;
    }

    /**
     * Fetch the next row of the result set $result as associative array
     * @param $result
     * @return array|null
     * @throws Exception
     */
    public function fetchArray($result): ?array
    {
        $result = $this->assert_resource($result);

        //null indicates no more records from this resource
        $record = $result->fetch_array(MYSQLI_NUM);

        if ($record === false) throw new Exception("Error fetching the result: ".$this->getError());

        return $record;
    }

    public function fetchResult($result): ?RawResult
    {
        $record = $this->fetch($result);
        if (is_array($record)) return new RawResult($record);
        return null;
    }

    public function free($result) : void
    {
        if ($result instanceof mysqli_result) {
            @$result->free();
        }
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

    public function queryFields(string $table)
    {
        return $this->query("show fields from $table");
    }

    public function fieldType(string $table, string $field_name)
    {
        $found = FALSE;
        $ret = FALSE;
        $res = $this->queryFields($table);
        while ($row = $this->fetch($res)) {
            if (strcmp($row["Field"], $field_name) == 0) {
                $ret = $row["Type"];
                $found = TRUE;
                break;
            }
        }
        $this->free($res);
        if (!$found) throw new Exception("Field [$field_name] does not exist in table: $table");
        return $ret;
    }

    public function tableExists(string $table)
    {
        $res = $this->query("show tables like '$table'");

        if ($res->num_rows < 1) return FALSE;
        return TRUE;
    }

    public function fetchTotalRows()
    {
        $ret = $this->query("SELECT FOUND_ROWS() as total");
        if (!$ret) throw new Exception("Unable to fetch found_rows(): " . $this->getError());
        $row = $this->fetch($ret);
        return $row["total"];
    }
}

?>
