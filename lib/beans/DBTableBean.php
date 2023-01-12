<?php
include_once("dbdriver/DBDriver.php");
include_once("iterators/SQLQuery.php");
include_once("sql/SQLSelect.php");
include_once("sql/SQLUpdate.php");
include_once("sql/SQLDelete.php");

abstract class DBTableBean
{

    /**
     * Primary key
     * @var string
     */
    protected $prkey;

    /**
     * Table
     * @var string
     */
    protected $table;

    /**
     * SQL to create this bean table into the DB
     * @var string
     */
    protected $createString;

    /**
     * @var DBDriver
     */
    protected $db;

    /**
     * Column names of this table as keys and the column storage type as value
     * @var array
     */
    protected $columns = array();


    /**
     * @var SQLSelect
     */
    protected $select;

    protected $error = "";

    /**
     * DBTableBean constructor. Specify the table name to work with in the '$table_name' parameter.
     * The default global DBDriver is used unless specified in the '$dbdriver' parameter
     * @param string $table_name
     * @param DBDriver|null $dbdriver
     * @throws Exception
     */
    public function __construct(string $table_name, DBDriver $dbdriver = NULL)
    {
        $this->table = $table_name;

        if ($dbdriver) {
            $this->db = $dbdriver;
        }
        else {
            $this->db = DBConnections::Get();
        }

        $bclass = get_class($this);

        if (!$this->db) throw new Exception("$bclass could not attach with DBDriver");

        $this->initFields();

        $this->select = new SQLSelect();

        $this->select->from = $this->table;

    }

    public function __destruct()
    {

    }

    protected function initFields()
    {

        $this->columns = array();

        if (!$this->db->tableExists($this->table)) {
            if (strlen($this->createString) < 1) {
                throw new Exception("Table '{$this->table}' was not found in the active connection and no create string is set to recreate the table");
            }
            $this->createTable();
        }

        if (!$this->db->tableExists($this->table)) {
            throw new Exception("Table '{$this->table}' is not available in the active DB connection");
        }

        $res = $this->db->queryFields($this->table);

        while ($row = $this->db->fetch($res)) {
            if (strcmp($row["Key"], "PRI") == 0) {
                $this->prkey = $row["Field"];
            }

            $field_name = $row["Field"];

            $this->columns[$field_name] = $row["Type"];
        }
        if ($res) $this->db->free($res);

        //debug("Storage Types for Bean: ".get_class($this), $this->storage_types);

    }

    protected function createTable()
    {

        try {
            $this->db->transaction();
            $res = $this->db->query($this->createString);
            if (!$res) throw new Exception("Unable to create the table structure: " . $this->db->getError());
            $this->db->commit();
            $this->db->free($res);
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }

    }

    public function getTableName()
    {
        return $this->table;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function setDB(DBDriver $db)
    {
        $this->db = $db;
    }

    public function getDB()
    {
        return $this->db;
    }

    /**
     * Return the table primary key
     * @return string
     */
    public function key(): string
    {
        return $this->prkey;
    }

    /**
     * Return column names of this table
     * @return array
     */
    public function columnNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * Return all the column names of this table as keys and the column storage type as value
     * @return array
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * SQLSelect that is used from the query functions.
     * Default fieldset list is empty
     * @return SQLSelect
     */
    public function select(): SQLSelect
    {
        return $this->select;
    }

    /**
     * Return true if '$column' exist in this table
     * @param string $column
     * @return bool
     */
    public function haveColumn(string $column): bool
    {
        return array_key_exists($column, $this->columns);
    }

    /**
     * Return the count of rows of this table
     * @return int
     * @throws Exception
     */
    public function getCount(): int
    {
        $qry = $this->query();
        $qry->select->fields()->set($this->prkey);
        return $qry->exec();
    }

    /**
     * Create default query selecting columns specified in the '$columns' list
     * If no columns are specified the default columns as set in 'this' SQLSelect instance are used
     * Current SQLSelect is cloned and passed to the SQLQuery constructor
     * @param string ...$columns
     * @return SQLQuery
     */
    public function query(string ...$columns): SQLQuery
    {
        $qry = new SQLQuery(clone $this->select, $this->prkey, $this->getTableName());
        $qry->setDB($this->db);
        $qry->setBean($this);

        $qry->select->fields()->set(...$columns);

        return $qry;
    }

    /**
     * Create query that select all columns of this table
     * @return SQLQuery
     */
    public function queryFull(): SQLQuery
    {
        $qry = $this->query();

        $columns = $this->columnNames();

        $qry->select->fields()->set(...$columns);

        return $qry;
    }

    /**
     * Query the table where column '$field' = '$value'
     * Result columns are the primary key, $field and all column names passed in $columns
     * @param string $field
     * @param string $value
     * @param int $limit
     * @param string ...$columns
     * @return SQLQuery
     */
    public function queryField(string $field, string $value, int $limit = 0, string ...$columns): SQLQuery
    {
        $field = $this->db->escape($field);
        $value = $this->db->escape($value);

        $qry = $this->query();
        $qry->select->fields()->set($this->prkey);
        $qry->select->fields()->set($field);

        $qry->select->fields()->set(...$columns);

        $qry->select->where()->add($field, "'$value'");
        if ($limit > 0) {
            $qry->select->limit = $limit;
        }

        return $qry;
    }

    /**
     * Retrieve single result row where column '$column' has value '$value'
     * @param string $column
     * @param string $value
     * @return array|null
     * @throws Exception
     */
    public function getResult(string $column, string $value) : ?array
    {
        $qry = $this->queryField($column, $value, 1);
        $qry->exec();
        return $qry->next();
    }

    /**
     * Retrieve the value of column '$column' where primary key value = '$id'
     * Return NULL if no matching result is found
     * @param int $id
     * @param string $field
     * @return string|null
     * @throws Exception
     */
    public function getValue(int $id, string $column): ?string
    {
        $column = $this->db->escape($column);

        $qry = $this->queryField($this->prkey, $id, 1);
        $qry->select->fields()->set($this->prkey, "`$column`");
        $qry->exec();
        if ($row = $qry->next()) {
            return $row[$column];
        }
        return NULL;
    }

    /**
     * Retrieve single result row where primary key value = '$id'
     * All columns are selected unless $columns is not empty then only primary key + $columns are selected
     * If ID is not found throws Exception
     * @param int $id
     * @param string ...$columns
     * @return array|null
     * @throws Exception
     */
    public function getByID(int $id, string ...$columns)
    {
        $qry = NULL;

        //use only columns passed in columns + the primary key
        if (count($columns) > 0) {
            $qry = $this->query();
            $qry->select->fields()->set($this->prkey);
            $qry->select->fields()->set(...$columns);
        }
        else {
            //match all columns
            $qry = $this->queryFull();
        }

        $qry->select->where()->add($this->prkey, $id);

        $qry->select->limit = 1;

        $num = $qry->exec();
        if ($num < 1) throw new Exception("No such ID");

        return $qry->next();
    }

    /**
     * Retrieve single result row where $column = '$value' , additional '$columns' are used if specified
     * @param string $column
     * @param int $value
     * @param string ...$columns
     * @return array|null
     * @throws Exception
     */
    public function getByRef(string $column, int $value, string ...$columns)
    {

        $qry = $this->queryField($column, $value, 1, ...$columns);

        $num = $qry->exec();
        if ($num < 1) throw new Exception("No such ID");

        return $qry->next();

    }

    /**
     * Handle code specified in Closure $code wrapped inside transaction/commit
     * Start new transaction only if $db parameter is NULL
     * @param Closure $code Code to execute in transaction
     * @param DBDriver|null $db parent DBDriver
     * @return int The number of affected rows after closure execution
     * @throws Exception
     */
    protected function handleTransaction(Closure $code, DBDriver $db = NULL)
    {
        $use_transaction = FALSE;

        $affectedRows = 0;

        if (!$db) {
            $use_transaction = TRUE;
            $db = $this->db;
            debug("Starting DB transaction with local DBDriver instance");
            $db->transaction();
        }
        else {
            debug("Not starting transaction - using DBDriver from function call paramater");
        }

        try {

            debug("Executing closure function");

            //either throw or succeed
            $code($db);

            $affectedRows = $db->affectedRows();
            debug("Closure Affected Rows: " . $affectedRows);

            debug("Closure function executed");

            if ($use_transaction) {
                debug("Committing DB transaction");
                $db->commit();
            }

            return $affectedRows;

        }
        catch (Exception $ex) {

            if ($use_transaction) {
                debug("Rolling back DB transaction - Exception: " . $ex->getMessage() . " - DBError: " . $db->getError());
                $this->error = $db->getError();
                $db->rollback();
            }

            throw $ex;
        }

    }

    /**
     * Delete where primary key = '$id'
     * Returns the number of affected rows
     * @param int $id
     * @param DBDriver|null $db
     * @return int
     * @throws Exception
     */
    public function delete(int $id, DBDriver $db = NULL) : int
    {

        $code = function (DBDriver $db) use ($id) {

            debug("Going to delete ID: $id");
            //TODO: select with joins clause needs table specification before the FROM statement in the DELETE statement
            $select = new SQLSelect();
            $select->from = $this->table;

            $delete = new SQLDelete($select);
            $delete->where()->add($this->prkey, $id);
            $sql = $delete->getSQL();

            debug("Executing SQL: $sql");

            if (!$db->query($sql)) throw new Exception("Unable to delete");

            $this->manageCache($id);
        };

        return $this->handleTransaction($code, $db);
    }

    /**
     * Delete where column '$column' have value '$value'
     * Specify all primary keys to skip from the delete operation in the $keep_ids array
     * @param string $column
     * @param string $value
     * @param DBDriver|null $db
     * @param array $keep_ids if not empty would not delete rows with primary keys specified in keep_ids
     * @throws Exception
     */
    public function deleteRef(string $column, string $value, DBDriver $db = NULL, array $keep_ids = array()) : int
    {
        if (!in_array($column, $this->columnNames())) throw new Exception("Column '$column' not found in this bean table");

        $code = function (DBDriver $db) use ($column, $value, $keep_ids) {

            $delete = new SQLDelete($this->select);
            $value = $db->escape($value);
            $delete->where()->add($column, "'$value'");

            if (count($keep_ids) > 0) {
                $keep_list_ids = implode(",", $keep_ids);
                $delete->where()->add($this->prkey, "($keep_list_ids)", " NOT IN ", " AND ");
            }

            $sql = $delete->getSQL();

            debug("Executing SQL: $sql");

            if (!$db->query($sql)) throw new Exception("Unable to deleteRef");

            $this->manageCache($value);
        };

        return $this->handleTransaction($code, $db);

    }

    public function needQuotes(string $key, &$value = "")
    {
        $storage_type = $this->columns[$key];

        if ($this->isNumeric($key)) return FALSE;

        if (strpos($storage_type, "datetime") !== FALSE || strpos($storage_type, "date") !== FALSE || strpos($storage_type, "timestamp") !== FALSE) {
            if (strlen(trim($value))==0) {
                $value = "NULL";
                return FALSE;
            }
            if (endsWith(trim($value), ")")) return FALSE;
            return TRUE;
        }
        return TRUE;
    }

    public function isNumeric($key): bool
    {
        $storage_type = $this->columns[$key];
        if ((strpos($storage_type, "decimal") !== FALSE) || (strpos($storage_type, "numeric") !== FALSE) || (strpos($storage_type, "integer") !== FALSE) || (strpos($storage_type, "float") !== FALSE) || (strpos($storage_type, "double") !== FALSE) ||

            (strpos($storage_type, "tinyint") !== FALSE) || (strpos($storage_type, "small") !== FALSE) || (strpos($storage_type, "mediumint") !== FALSE) || (strpos($storage_type, "int") !== FALSE) || (strpos($storage_type, "bigint") !== FALSE)) {
            return TRUE;
        }
        return FALSE;

    }

    /**
     * Insert $row into this table and return the last insert ID
     * @param array $row
     * @param DBDriver|null $db
     * @return int
     * @throws Exception
     */
    public function insert(array &$row, DBDriver $db = NULL): int
    {

        $insertID = -1;

        $values = array();

        $this->prepareInsertValues($row, $values);

        $code = function (DBDriver $db) use (&$values, &$insertID) {

            $sql = "INSERT INTO {$this->table} (" . implode(",", array_keys($values)) . ") VALUES (" . implode(",", $values) . ")";

            if (isset($GLOBALS["DEBUG_DBTABLEBEAN_INSERT"])) {
                debug(get_class($this) . " INSERT SQL: $sql");
            }

            if (!$db->query($sql)) {
                debug("Unable to insert: " . $db->getError() . " SQL: " . $sql);
                throw new Exception("Unable to insert: " . $db->getError());
            }

            //NOTE!!! lastID return the first auto_increment of a multi insert transaction
            $insertID = $db->lastID();

            $this->manageCache($insertID);
        };

        $this->handleTransaction($code, $db);

        return $insertID;
    }

    /**
     * @param int $id
     * @param array $row
     * @param DBDriver|null $db
     * @return int the number of affected rows from this update
     * @throws Exception
     */
    public function update(int $id, array &$row, DBDriver $db = NULL) : int
    {

        $values = array();
        $this->prepareUpdateValues($row, $values);

        $code = function (DBDriver $db) use ($id, &$values) {

            $update = new SQLUpdate($this->select);
            foreach ($values as $key => $value) {
                $update->set($key, $value);
            }
            $update->where()->add($this->prkey, $id);

            debug("UPDATE executing sql: " . $update->getSQL());

            if (!$db->query($update->getSQL())) {
                throw new Exception("Unable to update: " . $db->getError());
            }

            $this->manageCache($id);
        };

        return $this->handleTransaction($code, $db);

    }

    protected function manageCache($id)
    {
        //TODO: check path
        $cache_file = CACHE_PATH . "/" . get_class($this) . "/" . $id;
        if (!is_dir($cache_file)) return;
        try {
            @deleteDir($cache_file);
        }
        catch (Exception $e) {
            //
            debug("Unable to delete old cache directory: $cache_file");
        }
    }

    protected function prepareValues(&$row, &$values, $for_update)
    {

        $keys = array();

        foreach ($row as $key => $val) {
            //drop keys that are not fields from 'this' table
            if (!in_array($key, $this->columnNames())) continue;
            $keys[] = $key;
        }

        $values = array();

        foreach ($keys as $idx => $key) {
            $value = $row[$key];
            //debug("Checking key='$key' : Value: ".$value. " STRLEN: ".strlen($value)." is_null: ".is_null($value));

            //take first element of an array
            if (is_array($value)) {

                if (count($value) < 1) continue;
                $value = $value[0];

            }

            if (is_null($value)) {
                $values[$key] = "NULL";
            }
            else if ($this->isNumeric($key) && strlen($value) < 1) {
                $values[$key] = "NULL";
            }
            else {

                if ($this->needQuotes($key, $value) === TRUE) {
                    $values[$key] = "'" . $value . "'";
                }
                else {
                    $values[$key] = $value;
                }

            }

            //            if ($for_update === TRUE) {
            //
            //                $values[$key] = "$key=" . $values[$key];//already quoted
            //
            //            }
        }

    }

    protected function prepareInsertValues(&$row, &$values)
    {
        $this->prepareValues($row, $values, FALSE);
    }

    protected function prepareUpdateValues(&$row, &$values)
    {
        $this->prepareValues($row, $values, TRUE);
    }

}

?>