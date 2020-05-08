<?php

include_once("dbdriver/DBDriver.php");

class MySQLiDriver extends DBDriver
{
    private $connection = NULL;

    public static $conn_count = 0;

    public function __construct(DBConnectionProperties $conn, $open_new = true, $need_persistent = false)
    {

        if ($need_persistent) {
            $this->connection = mysqli_connect("p:" . $conn->host, $conn->user, $conn->pass, $conn->database, $conn->port);
        }
        else {
            $this->connection = mysqli_connect($conn->host, $conn->user, $conn->pass, $conn->database, $conn->port);
        }

        if (mysqli_connect_errno()) {
            throw new Exception("Unable to connect to database server(" . MySQLiDriver::$conn_count . "): " . mysqli_connect_error());
        }

        mysqli_autocommit($this->connection, false);
        mysqli_set_charset($this->connection, "utf8");

        mysqli_query($this->connection, "SET NAMES 'UTF8' COLLATE 'utf8_general_ci' ");
        mysqli_query($this->connection, "SET collation_connection = 'utf8_general_ci' ");

        mysqli_query($this->connection, "SET character_set_results = 'utf8'");
        mysqli_query($this->connection, "SET character_set_connection = 'utf8'");
        mysqli_query($this->connection, "SET character_set_client = 'utf8'");


        MySQLiDriver::$conn_count++;
        $ex = NULL;


    }

    public function __destruct()
    {
        $this->shutdown();
    }

    public function shutdown()
    {
        if (is_resource($this->connection)) mysqli_close($this->connection);
    }

    public function dateTime($add_days = 0, $interval_type = " DAY ")
    {
        $res = $this->query("SELECT DATE_ADD(now(), INTERVAL $add_days $interval_type) as datetime");
        $row = $this->fetch($res);
        return $row["datetime"];
    }

    public function timestamp()
    {
        $res = $this->query("SELECT UNIX_TIMESTAMP(CURRENT_TIMESTAMP) as datetime");
        $row = $this->fetch($res);
        return $row["datetime"];
    }

    public function query(string $str)
    {
        $res = mysqli_query($this->connection, $str);

        if (!$res) $this->error = mysqli_error($this->connection);

        return $res;
    }

    public function numRows($res) : int
    {
        return mysqli_num_rows($res);
    }

    public function numFields($res) : int
    {
        return mysqli_num_fields($res);
    }

    public function fieldName($res, int $pos)
    {

        $arr = mysqli_fetch_fields($res);
        return $arr[$pos];
    }

    protected function assert_resource(&$res)
    {
        if (!($res instanceof mysqli_result)) throw new Exception("No valid mysqli_resource passed");
    }

    public function fetch($res)
    {
        $this->assert_resource($res);

        $ret = mysqli_fetch_assoc($res) or $this->error = mysqli_error($this->connection);

        return $ret;
    }

    public function fetchArray($res)
    {
        $this->assert_resource($res);

        $ret = mysqli_fetch_array($res) or $this->error = mysqli_error($this->connection);

        return $ret;
    }

    public function fetchRow($res)
    {
        $this->assert_resource($res);

        $ret = mysqli_fetch_row($res) or $this->error = mysqli_error($this->connection);

        return $ret;
    }

    public function free($res)
    {
        if ($res instanceof mysqli_result) {
            @mysqli_free_result($res);
        }
    }

    public function lastID() : int
    {
        return mysqli_insert_id($this->connection);
    }

    public function commit()
    {
        $res = mysqli_query($this->connection, "COMMIT") or $this->error = mysqli_error($this->connection);
        return $res;
    }

    public function rollback()
    {
        $res = mysqli_query($this->connection, "ROLLBACK") or $this->error = mysqli_error($this->connection);
        return $res;
    }

    public function transaction()
    {
        $res = mysqli_query($this->connection, "START TRANSACTION") or $this->error = mysqli_error($this->connection);
        $res = mysqli_query($this->connection, "BEGIN") or $this->error = mysqli_error($this->connection);
        return $res;
    }

    public function escape(string $data)
    {
        return mysqli_real_escape_string($this->connection, $data);
    }

    public function queryFields($table)
    {
        return $this->query("show fields from $table");
    }

    public function fieldType(string $table, string $field_name)
    {
        $found = false;
        $ret = false;
        $res = $this->queryFields($table);
        while ($row = $this->fetch($res)) {
            if (strcmp($row["Field"], $field_name) == 0) {
                $ret = $row["Type"];
                $found = true;
                break;
            }
        }
        $this->free($res);
        if (!$found) throw new Exception("Field [$field_name] does not exist in table: $table");
        return $ret;
    }

    //enum('T1','TIR','CIM')


    public function tableExists(string $table)
    {
        $ret = $this->query("show tables like '$table'");
        $num = $this->numRows($ret);
        if ($num < 1) return FALSE;
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
