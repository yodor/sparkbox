<?php

include_once("dbdriver/DBDriver.php");


class PDOMySQLDriver extends DBDriver
{
    private $connection = NULL;

    public static $conn_count = 0;

    public function __construct(DBConnectionProperties $conn, $open_new = true, $need_persistent = false)
    {
        $this->PDO = true;

        $dsn = "mysql:dbname={$conn->database};host={$conn->host};charset=utf8;";
        $driver_options = array();
        if ($need_persistent) {
            $driver_options[PDO::ATTR_PERSISTENT] = true;
        }

        //blob and text fields buffer size 50MB
        $driver_options[PDO::MYSQL_ATTR_MAX_BUFFER_SIZE] = 1024 * 1024 * 50;
        $driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8' COLLATE 'utf8_general_ci'";
        //throws exception
        $this->connection = new PDO($dsn, $conn->user, $conn->pass, $driver_options);


        if (!$this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, false)) {
            throw new Exception("Unable to set attribute ATTR_AUTOCOMMIT");
        }

        //default is true?
        //$this->connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        //TODO: Check - will rise exception on errors
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//


        // 	$vars = $conn->getVariables();
        // 	foreach($vars as $dbvar=>$phpvar) {
        // 	    global $$phpvar;
        // 	    debug("Connection  @$dbvar = ".$$phpvar);
        // 	    if (!mysql_query("SET @$dbvar = '".$$phpvar."';",$this->connection)) {
        // 		debug("Unable to set @$dbvar variable to value: ".$$phpvar);
        // 	    }
        // 	    else {
        // 		debug("@$dbvar variable is now set to value: ".$$phpvar);
        // 	    }
        // 	}

        PDOMySQLDriver::$conn_count++;

    }

    public function __destruct()
    {
        $this->shutdown();
    }

    public function shutdown()
    {
        //if (is_resource($this->connection)) mysqli_close($this->connection);
        $this->connection = NULL;

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

    //!Table and Column names cannot be replaced by parameters in PDO.
    public function prepare($str)
    {
        return $this->connection->prepare($str);
    }

    public function execute($res, $arr = NULL)
    {
        if (!($res instanceof PDOStatement)) throw new Exception("No valid resource passed");
        if (is_array($arr)) {
            return $res->execute($arr);
        }
        return $res->execute();
    }

    public function query($str)
    {
        return $this->connection->query($str);

    }

    //$res is PDOMySQLStatement
    public function numRows($res)
    {
        if (!($res instanceof PDOStatement)) throw new Exception("No valid resource passed");
        return $res->rowCount();
    }

    //res is PDOMySQLStatement
    public function numFields($res)
    {
        if (!($res instanceof PDOStatement)) throw new Exception("No valid resource passed");
        return $res->columnCount();
    }

    public function fieldName($res, $pos)
    {
        if (!($res instanceof PDOStatement)) throw new Exception("No valid resource passed");

        $row = $this->connection->fetch(PDO::FETCH_ASSOC);

        $keys = array_keys($row);

        return $row[$keys[$pos]];
    }

    public function fetch($res)
    {
        if (!($res instanceof PDOStatement)) throw new Exception("No valid resource passed");
        return $res->fetch(PDO::FETCH_ASSOC);

    }

    public function fetchArray($res)
    {
        if (!($res instanceof PDOStatement)) throw new Exception("No valid result passed");
        return $res->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchRow($res)
    {
        if (!($res instanceof PDOStatement)) throw new Exception("No valid result  passed");
        return $res->fetch(PDO::FETCH_NUM);
    }

    public function free($res)
    {
        if (!$res) return;

        if (!($res instanceof PDOStatement)) throw new Exception("No valid result  passed");
        $res->closeCursor();
    }

    public function lastID()
    {
        return $this->connection->lastInsertId();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollback()
    {
        return $this->connection->rollback();
    }

    public function transaction()
    {

        return $this->connection->beginTransaction();
    }

    public function escapeString($data)
    {

        return substr($this->connection->quote($data), 1, -1);

    }

    public function queryFields($table)
    {
        return $this->query("show fields from $table");
    }

    public function fieldType($table, $field_name)
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
    public static function enum2array($enum_str)
    {
        $enum_str = str_replace("enum(", "", $enum_str);
        $enum_str = str_replace(")", "", $enum_str);
        $enum_str = str_replace("'", "", $enum_str);

        return explode(",", $enum_str);
    }

    public function tableExists($table)
    {
        $ret = $this->query("show tables like '$table'");
        $num = $this->numRows($ret);
        if ($num < 1) return FALSE;
        return TRUE;
    }

    public function fetchTotalRows()
    {
        $ret = $this->query("SELECT FOUND_ROWS() as total");
        if (!$ret) throw new Exception("Unable to fecth found_rows(): " . $this->getError());
        $row = $this->fetch($ret);
        return $row["total"];
    }
}

?>
