<?php

include_once("dbdriver/DBDriver.php");

class MySQLiDriver extends DBDriver
{
    /**
     * @var mysqli
     */
    private $conn = NULL;

    /**
     * MySQLiDriver constructor.
     * @param DBConnectionProperties $props
     * @param bool $open_new
     * @param bool $need_persistent
     * @throws Exception
     */
    public function __construct(DBConnectionProperties $props, $open_new = true, $need_persistent = false)
    {

        $host = $props->host;

        if ($need_persistent) {
            $host = "p:".$host;
        }

        $this->conn = @new mysqli($props->host, $props->user, $props->pass, $props->database, $props->port);

        if ($this->conn->connect_errno) {
            throw new Exception("Error connecting to database server: ". $this->conn->connect_error);
        }

        $this->conn->autocommit(false);
        $this->conn->set_charset("utf8");


        $this->conn->query("SET NAMES 'UTF8' COLLATE 'utf8_general_ci' ");
        $this->conn->query( "SET collation_connection = 'utf8_general_ci' ");

        $this->conn->query( "SET character_set_results = 'utf8'");
        $this->conn->query("SET character_set_connection = 'utf8'");
        $this->conn->query( "SET character_set_client = 'utf8'");


        DBConnections::$conn_count++;


    }

    public function __destruct()
    {
        $this->shutdown();
    }

    public function shutdown()
    {
        if ($this->conn) $this->conn->close();

    }

    public function getError() : string
    {
        return $this->conn->error;
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
        return $this->conn->query($str);
    }

    public function numRows($res) : int
    {
        $res = $this->assert_resource($res);
        return $res->num_rows;
    }

    public function numFields($res) : int
    {
        $res = $this->assert_resource($res);
        return $res->field_count;
    }

    public function fieldName($res, int $pos)
    {
        $res = $this->assert_resource($res);
        $arr = $res->fetch_fields();
        return $arr[$pos];
    }

    protected function assert_resource(&$res) : mysqli_result
    {
        if (!($res instanceof mysqli_result)) throw new Exception("No valid mysqli_resource passed");
        return $res;
    }

    public function fetch($res)
    {
        $res = $this->assert_resource($res);

        return $res->fetch_assoc();
    }

    public function fetchArray($res)
    {
        $res = $this->assert_resource($res);

        return $res->fetch_array(MYSQLI_NUM);

    }

    public function free($res)
    {
        if ($res instanceof mysqli_result) {
            @$res->free();
        }
    }

    public function lastID() : int
    {
        return $this->conn->insert_id;
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollback()
    {
        return $this->conn->rollback();
    }

    public function transaction()
    {
        return $this->conn->begin_transaction();
    }

    public function escape(string $data)
    {
        return $this->conn->real_escape_string($data);
    }

    public function queryFields(string $table)
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
