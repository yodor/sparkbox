<?php
include_once("dbdriver/DBDriver.php");
include_once("iterators/SQLQuery.php");
include_once("sql/SQLSelect.php");
include_once("sql/SQLInsert.php");
include_once("sql/SQLUpdate.php");
include_once("sql/SQLDelete.php");

abstract class DBTableBean
{

    /**
     * Primary key
     * @var string
     */
    protected string $prkey = "";

    /**
     * Table
     * @var string
     */
    protected string $table = "";

    /**
     * SQL to create this bean table into the DB
     * @var string
     */
    protected string $createString = "";

    /**
     * @var DBDriver
     */
    protected DBDriver $db;

    /**
     * Column names of this table as keys and the column storage type as value
     * @var array
     */
    protected array $columns = array();


    /**
     * @var SQLSelect
     */
    protected SQLSelect $select;


    /**
     * DBTableBean constructor. Specify the table name to work with in the '$table_name' parameter.
     * The default global DBDriver is used unless specified in the '$dbdriver' parameter
     * @param string $table_name
     * @param DBDriver|null $dbdriver
     * @throws Exception
     */
    public function __construct(string $table_name, ?DBDriver $dbdriver = NULL)
    {
        $this->table = $table_name;

        if ($dbdriver) {
            $this->db = $dbdriver;
        }
        else {
            $this->db = DBConnections::Open();
        }

        $this->initFields();

        $this->select = new SQLSelect();

        $this->select->from = $this->table;

    }

    public function __destruct()
    {

    }

    /**
     * @return void
     * @throws Exception
     */
    protected function initFields() : void
    {

        $this->columns = array();

        if (!$this->db->tableExists($this->table)) {
            if (strlen($this->createString) < 1) {
                throw new Exception("Table '$this->table' was not found in the active connection and no create string is set to recreate the table");
            }
            $this->createTable();
        }

        if (!$this->db->tableExists($this->table)) {
            throw new Exception("Table '$this->table' is not available in the active DB connection");
        }

        $result = $this->db->queryFields($this->table);
        if (!($result instanceof DBResult)) throw new Exception("Unable to query table fields");

        while ($row = $result->fetch()) {
            if (strcmp($row["Key"], "PRI") == 0) {
                $this->prkey = $row["Field"];
            }

            $field_name = $row["Field"];

            $this->columns[$field_name] = $row["Type"];
        }
        $result->free();
        //debug("Storage Types for Bean: ".get_class($this), $this->storage_types);

    }

    /**
     * @return void
     * @throws Exception
     */
    protected function createTable() : void
    {

        try {
            $this->db->transaction();
            $this->db->query($this->createString);
            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }

    }

    public function getTableName() : string
    {
        return $this->table;
    }

    public function setDB(DBDriver $db) : void
    {
        $this->db = $db;
    }

    public function getDB() : DBDriver
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

    public function columnType(string $columnName): string
    {
        if (!$this->haveColumn($columnName)) throw new RuntimeException("Column does not exist in this bean");
        return $this->columns[$columnName];
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
        $select = clone $this->select;
        if (sizeof($columns)>0) {
            $select->fields()->reset();
            $select->fields()->set(...$columns);
        }

        return $this->beanQuery($select);
    }


    /**
     * Create query that select all columns of this table
     * @return SQLQuery
     */
    public function queryFull(): SQLQuery
    {
        return $this->query(...$this->columnNames());
    }

    /**
     * Construct SQLSelect querying the table for column '$field' = '$value'
     * Result columns names are the primary key, '$field' and all column names passed in $columns list
     * SQLQuery is initialized using the resulting SQLSelect, '$this' bean and '$this->db' and returned from this method call
     * @param string $field Column being matched
     * @param string $value Value to match the column value with
     * @param int $limit Limit results count in the limit clause
     * @param string ...$columns Additional result columns for the query
     * @return SQLQuery Initialized with resulting select and using beanQuery method
     * @throws Exception
     */
    public function queryField(string $field, string $value, int $limit = 0, string ...$columns): SQLQuery
    {
        $field = $this->db->escape($field);
        $value = $this->db->escape($value);

        $select = clone $this->select;
        $select->fields()->set($this->prkey);
        $select->fields()->set($field);
        $select->fields()->set(...$columns);

        $select->where()->add($field, "'$value'");
        if ($limit > 0) {
            $select->limit = $limit;
        }

        return $this->beanQuery($select);
    }

    protected function beanQuery(SQLSelect $select) : SQLQuery
    {
        $qry = new SQLQuery($select, $this->prkey, $this->getTableName());
        $qry->setDB($this->db);
        $qry->setBean($this);
        return $qry;
    }

    /**
     * Retrieve single result (limit = 1) where column '$column' has value '$value'
     * primary key and $column columns are selected, select additional columns using $columns variadic array
     * @param string $column
     * @param string $value
     * @param string ...$columns
     * @return array|null
     * @throws Exception
     */
    public function getResult(string $column, string $value, string ...$columns) : ?array
    {
        $qry = $this->queryField($column, $value, 1, ...$columns);
        $qry->exec();
        return $qry->next();
    }

    /**
     * Retrieve the value of column '$column' where primary key value = '$id'
     * Return NULL if no matching result is found
     * @param int $id
     * @param string $column
     * @return string|null
     * @throws Exception
     */
    public function getValue(int $id, string $column): ?string
    {
        $column = $this->db->escape($column);

        $qry = $this->queryField($this->prkey, $id, 1, $column);

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
    public function getByID(int $id, string ...$columns) : ?array
    {
        //use only columns passed in columns + the primary key
        if (count($columns)<1) {
            $columns = $this->columnNames();
        }

        $qry = $this->queryField($this->prkey, $id, 1, ...$columns);

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
    public function getByRef(string $column, int $value, string ...$columns): ?array
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
    protected function handleTransaction(Closure $code, ?DBDriver $db = NULL) : int
    {
        $use_transaction = FALSE;

        if (!$db) {
            $use_transaction = TRUE;
            $db = $this->db;
            debug("Starting DB transaction with local DBDriver instance");
            $db->transaction();
        }
        else {
            debug("Not starting transaction - using DBDriver from function call parameter");
        }

        try {

            debug("Executing closure function");

            //either throw or succeed
            $code($db);

            $affectedRows = $db->affectedRows();
            debug("Closure finished - affected rows: " . $affectedRows);
            
            if ($use_transaction) {
                debug("Committing DB transaction");
                $db->commit();
            }

            return $affectedRows;

        }
        catch (Exception $ex) {

            if ($use_transaction) {
                debug("Rolling back DB transaction - Exception: " . $ex->getMessage() . " - DBError: " . $db->getError());
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
    public function delete(int $id, ?DBDriver $db = NULL) : int
    {

        $code = function (DBDriver $db) use ($id) {

            debug("Going to delete ID: $id");
            //TODO: select with joins clause needs table specification before the FROM statement in the DELETE statement
            $delete = new SQLDelete();
            $delete->from = $this->table;
            $delete->where()->add($this->prkey, $id);

            $sql = $delete->getSQL();

            //true or exception is thrown in db->query
            $db->query($sql);
              
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
    public function deleteRef(string $column, string $value, ?DBDriver $db = NULL, array $keep_ids = array()) : int
    {
        if (!in_array($column, $this->columnNames())) throw new Exception("Column '$column' not found in this bean table");

        $code = function (DBDriver $db) use ($column, $value, $keep_ids) {

            $delete = new SQLDelete($this->select);
            $value = $db->escape($value);
            if ($this->needQuotes($column, $value)) {
                $delete->where()->add($column, "'$value'");
            }
            else {
                $delete->where()->add($column, $value);
            }

            if (count($keep_ids) > 0) {
                $keep_list_ids = implode(",", $keep_ids);
                $delete->where()->add($this->prkey, "($keep_list_ids)", " NOT IN ", " AND ");
            }

            //fetch id of resulting rows first to properly manage the cache
            $select = new SQLSelect($delete);
            $select->fields()->reset();
            $select->fields()->set($this->prkey);

            $result = $db->query($select->getSQL());
            if (!($result instanceof DBResult)) throw new Exception("Unable to query affected ID list: ".$select->getSQL());

            $idlist = array();
            while ($data = $result->fetchResult()) {
                $idlist[] = intval($data->get($this->prkey));
            }
            debug("Affected ID list: ", $idlist);

            $db->query($delete->getSQL());

            foreach ($idlist as $id) {
                $this->manageCache((int)$id);
            }

        };

        return $this->handleTransaction($code, $db);

    }

    public function needQuotes(string $key, &$value = "") : bool
    {
        $storage_type = $this->columns[$key];

        if ($this->isNumeric($key)) return FALSE;

        if (str_contains($storage_type, "datetime") ||
            str_contains($storage_type, "date") ||
            str_contains($storage_type, "timestamp")) {
            if (strlen(trim($value))==0) {
                $value = "NULL";
                return FALSE;
            }
            if (str_ends_with(trim($value), ")")) return FALSE;
            return TRUE;
        }
        return TRUE;
    }

    public function isNumeric($key): bool
    {
        $storage_type = $this->columns[$key];
        if ((str_contains($storage_type, "decimal")) ||
            (str_contains($storage_type, "numeric")) ||
            (str_contains($storage_type, "integer")) ||
            (str_contains($storage_type, "float")) ||
            (str_contains($storage_type, "double")) ||

            (str_contains($storage_type, "tinyint")) ||
            (str_contains($storage_type, "small")) ||
            (str_contains($storage_type, "mediumint")) ||
            (str_contains($storage_type, "int")) ||
            (str_contains($storage_type, "bigint"))) {

            return TRUE;
        }
        return FALSE;

    }

    /**
     * Try to insert $row data into this table and return the last insert ID
     * @param array $row
     * @param DBDriver|null $db
     * @return int last insert ID
     * @throws Exception
     */
    public function insert(array $row, ?DBDriver $db = NULL): int
    {

        $insertID = -1;

        $insert = new SQLInsert();
        $insert->from = $this->table;
        $this->prepareValues($row, $insert);

        $code = function (DBDriver $db) use (&$insertID , $insert) {

            $db->query($insert->getSQL());

            //NOTE!!! lastID return the first auto_increment of a multi insert transaction
            $insertID = $db->lastID();

            $this->manageCache($insertID);
        };

        $this->handleTransaction($code, $db);

        return $insertID;
    }

    /**
     * Try to update $id of this table with row data
     * @param int $id
     * @param array $row
     * @param DBDriver|null $db
     * @return int the number of affected rows from this update
     * @throws Exception
     */
    public function update(int $id, array $row, ?DBDriver $db = NULL) : int
    {
        $update = new SQLUpdate($this->select);
        $update->where()->add($this->prkey, $id);
        $this->prepareValues($row, $update);

        $code = function (DBDriver $db) use ($id, $update) {

            $db->query($update->getSQL());

            $this->manageCache($id);

        };

        //handle transaction returns the number of affected rows
        return $this->handleTransaction($code, $db);

    }

    protected function manageCache(int $id) : void
    {
        //TODO: check path
        $cache_file = CACHE_PATH . "/" . get_class($this) . "/" . $id;
        debug("Checking cache folder: '$cache_file'");
        if (!is_dir($cache_file)) {
            debug("'$cache_file' not a folder");
            return;
        }
        try {
            debug("Removing folder '$cache_file'");
            deleteDir($cache_file);
            debug("Removing folder '$cache_file' complete");
        }
        catch (Exception $e) {
            //
            debug("Unable to delete cache folder: $cache_file");
        }
    }

    protected function prepareValues(array $row, SQLStatement $statement)
    {

        $keys = array();

        foreach ($row as $key => $val) {
            //drop keys that are not columns of 'this' table
            if (!in_array($key, $this->columnNames())) continue;
            $keys[] = $key;
        }

        foreach ($keys as $key) {
            $value = $row[$key];
            //debug("Checking key='$key' : Value: ".$value. " STRLEN: ".strlen($value)." is_null: ".is_null($value));

            //TODO check usage
            if (is_array($value)) {
                if (count($value) < 1) continue;
                $value = $value[0];
            }

            if (is_null($value)) {
                $value = "NULL";
            }
            else if ($this->isNumeric($key) && strlen($value) < 1) {
                $value = "NULL";
            }
            else if ($this->needQuotes($key, $value) === TRUE) {
                $value = "'" . $value . "'";
            }

            $statement->set($key, $value);
        }

    }

}

?>
