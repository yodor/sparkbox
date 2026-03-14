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
     * @return void
     * @throws Exception
     */
    public function connect() : void
    {
        if ($this->isConnected()) {
            Debug::ErrorLog("Connection is already open");
            return;
        }

        $host = $this->props->host;


        $this->conn = @new mysqli($host, $this->props->user, $this->props->pass, $this->props->database, $this->props->port);

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

        Debug::ErrorLog("Opening connection to database server");
        SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::OPENED));
    }

    public function disconnect() : void
    {
        if (is_null($this->conn)) {
            //Debug::ErrorLog("Handle is already closed");
            return;
        }

        $this->conn->close();
        $this->conn = null;
        SparkEventManager::emit(new DBDriverEvent(DBDriverEvent::CLOSED));
    }

    public function isConnected(): bool
    {
        return !is_null($this->conn);
    }

    public function connection(): mysqli
    {
        return $this->conn;
    }

    public function getError(): string
    {
        return $this->conn->error;
    }


    public function hasResultSet(): bool
    {
        return false;
    }

    public function queryRaw(string $sqlText): DBResult
    {
        try {

            $res = $this->conn->query($sqlText);
            if ($res === false) throw new Exception("Result is false");

            return new MySQLiResult();

        }
        catch (Exception $e) {
            Debug::ErrorLog("Error: ".$e->getMessage()." | Connection Error: ".$this->conn->error." | SQL: $sqlText");
            throw new Exception("Error: ".$e->getMessage());
        }
    }

    /**
     * For successful queries which produce a result set, such as SELECT, SHOW, DESCRIBE or EXPLAIN,
     * mysqli_query will return a mysqli_result object.
     * For other successful queries mysqli_query will return true else throws exception
     * @param SQLStatement $statement
     * @return DBResult
     * @throws Exception
     */
    public function query(SQLStatement $statement) : DBResult
    {

        try {
            $sql = $statement;
            if ($statement instanceof SQLStatement) {
                $sql = $statement->getSQL();
            }
            $res = $this->conn->query($sql);
            if ($res === false) throw new Exception("Result is false");

            return new MySQLiResult($res);

        }
        catch (Exception $e) {
            Debug::ErrorLog("Error: ".$e->getMessage()." | Connection Error: ".$this->conn->error." | SQL: $sql");
            throw new Exception("Error: ".$e->getMessage());
        }

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

    public function columnTypes(string $tableName): array
    {
        //Field   //Type             //Null //Key   //Default   //Extra
        //userID  //int(11) unsigned //NO   //PRI   //NULL      //auto_increment
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

}