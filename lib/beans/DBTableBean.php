<?php
include_once("dbdriver/DBDriver.php");
include_once("dbdriver/IDBDriverAccess.php");
include_once("iterators/SelectQuery.php");
include_once("sql/SQLSelect.php");
include_once("sql/SQLInsert.php");
include_once("sql/SQLUpdate.php");
include_once("sql/SQLDelete.php");
include_once("sql/RawSQLSelect.php");
include_once("objects/ISerializable.php");
include_once("objects/IUnserializable.php");

abstract class DBTableBean implements IDBDriverAccess, ISerializable, IUnserializable
{

    /**
     * Table primary key column name
     * @var string
     */
    protected string $prkey = "";

    /**
     * Table name
     * @var string
     */
    protected string $table = "";

    /**
     * SQL to create this bean table into the DB
     * @var string
     */
    protected string $createString = "";

    /**
     * @var DBDriver|null
     */
    protected ?DBDriver $db = null;

    /**
     * Column names of this table as keys and the column storage type as value
     * @var array
     */
    protected array $columns = array();


    /**
     * @var SQLSelect|null
     */
    protected ?SQLSelect $select = null;


    /**
     * DB table access wrapper.
     * The default connection DBDriver will be used if '$driver' parameter is null
     * @param string $tableName
     * @param DBDriver|null $driver
     * @throws Exception
     */
    public function __construct(string $tableName, ?DBDriver $driver = NULL)
    {
        $this->table = $tableName;

        if (!is_null($driver)) {
            $this->db = $driver;
        }
        else {
            $this->db = DBConnections::Driver();
        }

        $this->initialize();
    }


    /**
     * @return void
     * @throws Exception
     */
    protected function initialize() : void
    {

        if (!DBTableBean::TableExists($this->table)) {
            if (strlen($this->createString) < 1) {
                throw new Exception("Table '$this->table' was not found in the active connection and no create string is set to recreate the table");
            }
            DBTableBean::CreateTable($this->createString);
        }

        $this->initColumnTypes();

        $this->select = new SQLSelect();
        $this->select->from = $this->table;

        //Debug::ErrorLog("DBTableBean[$this->table]");
    }

    protected function initColumnTypes() : void
    {
        $this->columns = array();

        $columnTypes = DBTableBean::ColumnTypes($this->table);

        foreach ($columnTypes as $columnName => $details) {
            if (strcmp($details["Key"], "PRI") === 0) {
                $this->prkey = $columnName;
            }
            $this->columns[$columnName] = $details["Type"];
        }

        //Debug::ErrorLog("Columns: ", $this->columns);
    }

    /**
     * Describe table columns using format
     * * Field,Type,Null,Key,Default,Extra
     * * ex Field=>userID , Type=>int(11) unsigned, Null=>NO, Key=>PRI, Default=>NULL, Extra=>auto_increment
     * @param string $tableName
     * @return array<string, array{Field: string, Type:string, Null:string, Key:string, Default:string, Extra:string}>
     * @throws Exception
     */
    protected static function ColumnTypes(string $tableName): array
    {
        $types = array();

        $query = new SelectQuery(new RawSQLSelect("DESCRIBE `$tableName`"));
        try {
            $query->exec();
            while ($data = $query->next()) {
                $columnName = $data["Field"];
                $types[$columnName] = $data;
            }
        }
        catch (Exception $ex) {
            Debug::ErrorLog("DESCRIBE failed: ".$ex->getMessage());
        }
        finally {
            $query->free();
        }

        return $types;
    }

    /**
     * Check if a table named - '$tableName' exist in the current connection
     * @param string $tableName
     * @return bool
     */
    protected static function TableExists(string $tableName): bool
    {
        $tableok = false;
        $query = new SelectQuery(new RawSQLSelect("SELECT 1 FROM `{$tableName}` LIMIT 1"));

        try {
            $query->exec(); // throw if table does not exist as PDO::ERRMODE_EXCEPTION is true
            $query->next();
            $tableok = true;

        } catch (Exception $e) {
            Debug::ErrorLog("Check failed: ".$e->getMessage());
            $tableok = false;
        }
        finally {
            $query->free();
        }
        return $tableok;
    }

    /**
     * @param string $createText
     * @return void
     * @throws Exception
     */
    protected static function CreateTable(string $createText) : void
    {
        $query = new SelectQuery(new RawSQLSelect($createText));
        try {
            $query->exec();
        }
        catch (Exception $e) {
            throw $e;
        }
        finally {
            $query->free();
        }
    }

    public function getTableName() : string
    {
        return $this->table;
    }

    public function setDB(DBDriver $driver) : void
    {
        $this->db = $driver;
    }

    public function getDB() : ?DBDriver
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
        $qry->stmt->reset();
        $qry->stmt->set($this->prkey);
        $qry->stmt->limit = "0";
        return $qry->count();

    }

    /**
     * Create default query cloning $this->select().
     * If no columns are specified all columns from $this->select() are used.
     *
     * @param string ...$columns
     * @return SelectQuery
     * @throws Exception
     */
    public function query(string ...$columns): SelectQuery
    {
        $select = clone $this->select;
        if (sizeof($columns)>0) {
            $select->reset();
            $select->set(...$columns);
        }

        return $this->beanQuery($select);
    }

    /**
     * Create query that select all columns of this table
     * @return SelectQuery
     * @throws Exception
     */
    public function queryFull(): SelectQuery
    {
        return $this->query(...$this->columnNames());
    }

    /**
     * Create new SelectQuery selecting all rows matching column '$field' = '$value'
     * Result columns names are the primary key, '$field' and all column names passed in $columns list
     * SelectQuery is initialized using the resulting SQLSelect, '$this' bean and '$this->db' and returned from this method call
     *
     * @param string $field Column being matched
     * @param string $value Value to match the column value with
     * @param int $limit Limit results count in the limit clause
     * @param string ...$columns Additional result columns for the query
     * @return SelectQuery Initialized with resulting select and using beanQuery method
     * @throws Exception
     */
    public function queryField(string $field, string $value, int $limit = 0, string ...$columns): SelectQuery
    {
        $select = clone $this->select;
        $select->set($this->prkey);
        $select->set($field);
        $select->set(...$columns);

        $select->where()->add($field, $value);
        if ($limit > 0) {
            $select->limit = $limit;
        }

        return $this->beanQuery($select);
    }

    /**
     * Return new SelectQuery using the select passed
     *
     * @param SQLSelect $select
     * @return SelectQuery
     */
    protected function beanQuery(SQLSelect $select) : SelectQuery
    {
        $qry = new SelectQuery($select, $this->prkey, $this->getTableName());
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
//        $column = $this->db->escape($column);

        $qry = $this->queryField($this->prkey, $id, 1, $column);

        $qry->exec();
        if ($row = $qry->next()) {
            return $row[$column];
        }
        return NULL;
    }

    /**
     * Retrieve single result row where primary key value = '$id' - Uses the current bean select
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
        $qry->exec();

        if ($result = $qry->next()) {
            return $result;
        }
        
        throw new Exception("No such ID");
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
        $qry->exec();

        if ($result = $qry->next()) {
            return $result;
        }
        throw new Exception("No such Ref");


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
            Debug::ErrorLog("Starting DB transaction using the bean assigned driver connection");
            $db->transaction();
        }
        else {
            Debug::ErrorLog("Not starting DB transaction - received driver connection from function call parameter");
        }

        try {

            Debug::ErrorLog("Executing closure function");
            //either throw or succeed
            $code($db);
            $affectedRows = $db->affectedRows();
            Debug::ErrorLog("Closure finished - affected rows: " . $affectedRows);
            
            if ($use_transaction) {
                $db->commit();
            }

            return $affectedRows;

        }
        catch (Exception $ex) {

            if ($use_transaction) {
                Debug::ErrorLog("Rolling back DB transaction - Exception: " . $ex->getMessage() . " - DBError: " . $db->getError());
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

            Debug::ErrorLog("Going to delete ID: $id");
            //TODO: select with joins clause needs table specification before the FROM statement in the DELETE statement
            $delete = new SQLDelete();
            $delete->from = $this->table;
            $delete->where()->add($this->prkey, $id);

            $db->query($delete)->free();

            $this->manageCache($id, $db);
        };

        return $this->handleTransaction($code, $db);

    }

    /**
     * Delete where column '$column' have value '$value'
     * Specify all primary keys to skip from the delete operation in the $keep_ids array
     * @param string $column
     * @param string $value
     * @param DBDriver|null $db
     * @param array $keep_ids
     * @return int
     * @throws Exception
     */
    public function deleteRef(string $column, string $value, ?DBDriver $db = NULL, array $keep_ids = array()) : int
    {
        if (!in_array($column, $this->columnNames())) throw new Exception("Column '$column' not found in this bean table");


        $code = function (DBDriver $db) use ($column, $value, $keep_ids) {

            Debug::ErrorLog("Keeping: ", $keep_ids);

            $delete = new SQLDelete($this->select);
            $delete->where()->add($column, $value);

            //delete all referenced but keep the ids passed inside $keep_ids
            if (count($keep_ids) > 0) {
                $keep_list = $delete->bindList($keep_ids);
                $delete->where()->addExpression("$this->prkey NOT IN ($keep_list)");
            }
            $delete->setMeta("DeleteRef: ".get_class($this));

            //fetch id of resulting rows first to properly manage the cache
            //copy $delete whereset and bindings
            $select = new SQLSelect($delete);
            $select->reset();
            $select->set($this->prkey);

            $result = $db->query($select);
            $idlist = array();
            while ($data = $result->fetch()) {
                $idlist[] = (int)$data[$this->prkey];
            }
            $result->free();

            Debug::ErrorLog("Manage cache using: ", $idlist);

            $db->query($delete)->free();

            $affectedRows = $db->affectedRows();
            Debug::ErrorLog("Affected rows from DeleteRef: ". $affectedRows);

            foreach ($idlist as $id) {
                $this->manageCache((int)$id, $db);
            }

        };
        return $this->handleTransaction($code, $db);

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
     * Create SQLInsert construct binding values with the bean columns.
     * Values with keys that do not correspond to columns of this bean are dropped.
     * Try to insert $id of this table with row data and return the last insert ID
     * Executed in transaction.
     *
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
        $this->bindValues($row, $insert);

        $code = function (DBDriver $db) use (&$insertID , $insert) {

            $db->query($insert)->free();
            $insertID = $db->lastID();

            $this->manageCache($insertID, $db);

        };

        $affectedRows = $this->handleTransaction($code, $db);

        return $insertID;
    }

    /**
     * Create SQLUpdate construct binding values with the bean columns.
     * Values with keys that do not correspond to columns of this bean are dropped.
     * Try to update $id of this table with row data.
     * Executed in transaction if $db passed is NULL
     *
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
        $this->bindValues($row, $update);

        $code = function (DBDriver $db) use ($id, $update) {
            $db->query($update)->free();
            $this->manageCache($id, $db);
        };

        //handle transaction returns the number of affected rows
        return $this->handleTransaction($code, $db);

    }

    protected function manageCache(int $id, DBDriver $db) : void
    {
        if ($this instanceof SparkCacheBean) return;

        $cache_file = Spark::PathParts(Spark::Get(Config::CACHE_PATH) , get_class($this) , $id);
        Debug::ErrorLog("Checking cache folder: '$cache_file'");
        if (!is_dir($cache_file)) {
            Debug::ErrorLog("'$cache_file' not a folder");
            return;
        }
        try {
            Debug::ErrorLog("Removing folder '$cache_file'");
            Spark::DeleteFolder($cache_file);
            Debug::ErrorLog("Removing folder '$cache_file' complete");
        }
        catch (Exception $e) {
            //
            Debug::ErrorLog("Unable to delete cache folder: $cache_file");
        }

        //TODO
//        try {
//            $delete = new SQLDelete();
//            $delete->from = "sparkcache";
//            $delete->where()->add("className", get_class($this));
//            $delete->where()->add("beanID", $id);
//            $query = new DBQuery();
//            $query->exec($delete, $db);
//        }
//        catch (Exception $e) {
//
//            Debug::ErrorLog("Unable to delete from sparkcache table: ".$e->getMessage());
//        }

    }

    protected function bindValues(array $row, SQLInsert|SQLUpdate $statement) : void
    {
        $columnNames = $this->columnNames();

        foreach ($row as $key => $value) {
            // 1. skip keys that do not reference column names in this bean
            if (!in_array($key, $columnNames)) continue;

            //TODO: check usage and throw
            // 2. take the first element from array only
            if (is_array($value)) {
                if (count($value) < 1) continue;
                $value = $value[0];
            }

            // 3.For numeric columns set to null
            else if ($this->isNumeric($key) && (strlen((string)$value) < 1 || strcasecmp((string)$value, "null") === 0)) {
                $value = null;
            }

            //set column values - auto-binding
            $statement->set($key, $value);

        }
    }

    public function __serialize() : array
    {
        return array("table" => $this->table, "connection_name"=>$this->db->getConnectionName());
    }

    public function __unserialize(array $data) : void
    {
        $this->table = $data["table"];
        //should already be present inside DBConnections
        $this->db = DBConnections::CreateDriver($data["connection_name"]);
        $this->initialize();
    }

}