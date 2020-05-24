<?php
include_once("dbdriver/DBDriver.php");
include_once("iterators/SQLQuery.php");
include_once("utils/SQLSelect.php");

abstract class DBTableBean
{

    /**
     * Base class to work with DB Tables
     *
     */

    protected $prkey = "";

    protected $table = "";

    protected $fields = array();

    protected $createString = "";

    /**
     * @var DBDriver|null
     */
    protected $db = NULL;

    protected $storage_types = NULL;

    protected static $instances = array();

    /**
     * @var SQLSelect|null
     */
    protected $select = NULL;

    protected $error = "";

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

        if (!isset(self::$instances[$bclass])) {
            self::$instances[$bclass] = $this;
        }

        $this->select = new SQLSelect();
        $this->select->fields = " * ";
        $this->select->from = $this->table;

    }

    public static function instance($class)
    {
        if (isset(self::$instances[$class])) {
            return self::$instances[$class];
        }
        return new $class();
    }

    public function __destruct()
    {
        //$this->db->free($this->iterator);
    }

    protected function initFields()
    {

        $this->storage_types = array();

        if (!$this->db->tableExists($this->table)) {
            if (strlen($this->createString) < 1) {
                throw new Exception("Table [{$this->table}] was not found in the active connection and no create string set to recreate the table.");
            }
            $this->createTable();
        }

        if (!$this->db->tableExists($this->table)) {
            throw new Exception("Table [{$this->table}] is not available in the active connection.");
        }

        $res = $this->db->queryFields($this->table);

        while ($row = $this->db->fetch($res)) {
            if (strcmp($row["Key"], "PRI") == 0) {
                $this->prkey = $row["Field"];
            }

            $field_name = $row["Field"];

            $this->fields[] = $field_name;

            $this->storage_types[$field_name] = $row["Type"];
        }
        if ($res) $this->db->free($res);

        //       debug("Storage Types for Bean: ".get_class($this), $this->storage_types);

    }

    protected function createTable()
    {
        $this->db->transaction();
        $this->db->query($this->createString);
        $this->db->commit();
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
     * @return string Table primary key
     */
    public function key(): string
    {
        return $this->prkey;
    }

    public function fields(): array
    {
        return $this->fields;
    }

    public function storageTypes()
    {
        return $this->storage_types;
    }

    public function select(): SQLSelect
    {
        return $this->select;
    }

    public function getCount(): int
    {
        $qry = $this->query();
        $qry->select->fields = " {$this->prkey} ";
        return $qry->exec();
    }

    public function haveField($field_name): bool
    {
        return in_array($field_name, $this->fields);
    }

    public function queryID(int $id): SQLQuery
    {
        return $this->queryField($this->prkey, $id, 1);
    }

    public function queryField(string $field, string $value, int $limit = 0, string $sign = " = "): SQLQuery
    {
        $field = $this->db->escape($field);
        $value = $this->db->escape($value);

        $qry = $this->query();
        $qry->select->where = " $field $sign '$value' ";
        if ($limit > 0) {
            $qry->select->limit = " $limit ";
        }
        return $qry;
    }

    /**
     * Clone this select and return as SQLQuery
     * @return SQLQuery
     */
    public function query(): SQLQuery
    {
        $qry = new SQLQuery(clone $this->select(), $this->prkey, $this->getTableName());
        $qry->setDB($this->db);
        $qry->setBean($this);
        return $qry;
    }

    public function findFieldValue(string $field_name, string $field_value)
    {
        $qry = $this->queryField($field_name, $field_value, 1);
        $qry->exec();
        return $qry->next();
    }

    public function fieldValues(int $id, array $field_names): ?array
    {
        $qry = $this->queryField($this->prkey, $id, 1);
        foreach ($field_names as $idx => $value) {
            $field_names[$idx] = "`" . $this->db->escape($value) . "`";
        }
        $qry->select->fields = " {$this->prkey}, " . implode(",", $field_names);
        $qry->exec();
        if ($row = $qry->next()) {
            return $row;
        }
        return NULL;
    }

    public function fieldValue(int $id, string $field): ?string
    {
        $field = $this->db->escape($field);

        $qry = $this->queryField($this->prkey, $id, 1);
        $qry->select->fields = " {$this->prkey}, `$field` ";
        $qry->exec();
        if ($row = $qry->next()) {
            return $row[$field];
        }
        return NULL;
    }

    /**
     * @param int $id
     * @param array $fields
     * @return mixed
     * @throws Exception
     */
    public function getByID(int $id, array $fields = array())
    {
        $qry = $this->queryID($id);

        if (count($fields) == 0) {
            $fields[] = " * ";
        }
        else {
            $fields[] = $this->prkey;
        }

        $qry->select->fields = implode(",", $fields);

        $num = $qry->exec();
        if ($num < 1) throw new Exception("No such ID");

        return $qry->next();
    }

    /**
     * @param string $refKey
     * @param int $refID
     * @param array $fields
     * @return mixed
     * @throws Exception
     */
    public function getByRef(string $refKey, int $refID, array $fields = array())
    {

        $qry = $this->queryField($refKey, $refID, 1);

        if (count($fields) == 0) {
            $fields[] = " * ";
        }
        else {
            $fields[] = $this->prkey;
        }

        $qry->select->fields = implode(",", $fields);

        $num = $qry->exec();
        if ($num < 1) throw new Exception("No such ID");

        return $qry->next();

    }

    /**
     * Handle db code wrapped inside transaction/commit
     *
     * @param Closure $code Code to execute in transaction
     * @param DBDriver|null $db parent DBDruver
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
            debug("Closure Affected Rows: ".$affectedRows);

            debug("Closure function executed");

            if ($use_transaction) {
                debug("Committing DB transaction");
                $db->commit();
            }

            return $affectedRows;

        }
        catch (Exception $ex) {

            if ($use_transaction) {
                debug("Rolling back DB transaction - Exception: " . $ex->getMessage()." - DBError: ".$db->getError());
                $this->error = $db->getError();
                $db->rollback();
            }

            throw $ex;
        }


    }

    /**
     * @param int $id
     * @param DBDriver|null $db
     * @throws Exception
     */
    public function delete(int $id, DBDriver $db = NULL)
    {

        $code = function (DBDriver $db) use ($id) {

            debug("Going to delete ID: $id");

            $sql = "DELETE FROM {$this->table} WHERE {$this->prkey}='$id'";
            if ($this->select->where) {
                $sql .= " AND {$this->select->where}";
            }

            debug("Executing SQL: $sql");

            if (!$db->query($sql)) throw new Exception("Unable to delete");

            $this->manageCache($id);
        };

        return $this->handleTransaction($code, $db);
    }

    /**
     * Delete where refkey=refval and primary key is not inside keep_ids
     * @param string $refkey
     * @param string $refval
     * @param DBDriver|null $db
     * @param array $keep_ids
     * @throws Exception
     */
    public function deleteRef(string $refkey, string $refval, DBDriver $db = NULL, $keep_ids = array())
    {
        if (!in_array($refkey, $this->fields)) throw new Exception("Field '$refkey' not found in this bean");

        $code = function (DBDriver $db) use ($refkey, $refval, $keep_ids) {

            $sql = "DELETE FROM {$this->table} WHERE $refkey='$refval'";

            if (count($keep_ids) > 0) {
                $keep_list_ids = implode(",", $keep_ids);
                $sql .= " AND ({$this->prkey} NOT IN ($keep_list_ids)) ";
            }

            debug("Executing SQL: $sql");

            if (!$db->query($sql)) throw new Exception("Unable to deleteRef");

            $this->manageCache($refval);
        };

        $this->handleTransaction($code, $db);

    }

    public function toggleField(int $id, string $field)
    {

        $field = $this->db->escape($field);
        if (!in_array($field, $this->fields)) throw new Exception("Field '$field' not found in this bean");

        try {

            $this->db->transaction();

            $update = new SQLUpdate($this->select);
            $update->set[$field]=" NOT $field ";
            $update->where = " {$this->prkey} = $id ";

            if (!$this->db->query($update->getSQL())) throw new Exception("toggleField DB Error: " . $this->db->getError());

            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
            $this->error = $this->db->getError();
            throw $e;
        }
    }

    public function needQuotes(string $key, &$value = "")
    {
        $storage_type = $this->storage_types[$key];
        //        echo "$key=>$storage_type | ";

        // 	  if (strpos($storage_type,"char")!==false || strpos($storage_type,"text")!==false || strpos($storage_type,"blob")!==false  ||  strpos($storage_type,"enum")!==false) {
        // 		return true;
        // 	  }
        // 	  if (strpos($storage_type, "bool")!==false) {
        // 		return true;
        // 	  }
        if ($this->isNumeric($key))return FALSE;

        if (strpos($storage_type, "date") !== FALSE || strpos($storage_type, "timestamp") !== FALSE) {
            return FALSE;
            //if (endsWith($value, "()")) return FALSE;
            //return TRUE;
        }
        return TRUE;
    }

    public function isNumeric($key): bool
    {
        $storage_type = $this->storage_types[$key];
        if ((strpos($storage_type, "decimal") !== FALSE) || (strpos($storage_type, "numeric") !== FALSE) || (strpos($storage_type, "integer") !== FALSE) || (strpos($storage_type, "float") !== FALSE) || (strpos($storage_type, "double") !== FALSE) ||

            (strpos($storage_type, "tinyint") !== FALSE) || (strpos($storage_type, "small") !== FALSE) || (strpos($storage_type, "mediumint") !== FALSE) || (strpos($storage_type, "int") !== FALSE) || (strpos($storage_type, "bigint") !== FALSE)) {
            return TRUE;
        }
        return FALSE;

    }

    public function insert(array &$row, DBDriver $db = NULL): int
    {

        $insertID = -1;

        $values = array();

        $this->prepareInsertValues($row, $values);

        $code = function(DBDriver $db) use(&$values, &$insertID) {

            $sql = "INSERT INTO {$this->table} (" . implode(",", array_keys($values)) . ") VALUES (" . implode(",", $values) . ")";

            if (isset($GLOBALS["DEBUG_DBTABLEBEAN_INSERT"])) {
                debug(get_class($this) . " INSERT SQL: $sql");
            }

            if (!$db->query($sql)) throw new Exception("Unable to insert");

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
    public function update(int $id, array &$row, DBDriver $db = NULL)
    {

        $values = array();
        $this->prepareUpdateValues($row, $values);

        $code = function(DBDriver $db) use($id, &$values) {

            $update = new SQLUpdate($this->select);
            foreach ($values as $key=>$value) {
                $update->set[$key] = $value;
            }
            $update->appendWhere("{$this->prkey} = $id");

            debug("UPDATE executing sql: ".$update->getSQL());

            if (!$db->query($update->getSQL())) {
                throw new Exception("Unable to update: ".$db->getError());
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
            if (!in_array($key, $this->fields)) continue;
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
