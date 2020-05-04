<?php
include_once("lib/dbdriver/DBDriver.php");
include_once("lib/iterators/SQLQuery.php");
include_once("lib/utils/SQLSelect.php");

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

//    protected $filter = FALSE;
//
//    protected $last_iterator_sql = "";

    protected $db = NULL;

    protected $storage_types = NULL;

//    protected $iterator = NULL;

    protected static $instances = array();

    protected static $use_prepared_statement = FALSE;

    /**
     * @var SQLSelect|null
     */
    protected $sqlSelect = null;

    public function __construct($table_name, $dbdriver = NULL)
    {
        $this->table = $table_name;

        if ($dbdriver) {
            $this->db = $dbdriver;
        }
        else {
            $this->db = DBDriver::Get();
        }

        $bclass = get_class($this);

        if (!$this->db) throw new Exception("DBTableBean::$bclass could not attach with DBDriver");

        $this->initFields();


        if (!isset(self::$instances[$bclass])) {
            self::$instances[$bclass] = $this;
        }

        $this->sqlSelect = new SQLSelect();
        $this->sqlSelect->fields = " * ";
        $this->sqlSelect->from = $this->table;


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

    public function getError()
    {
        return $this->db->getError();
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
        return $this->sqlSelect;
    }

//    public function getLastIteratorSQL()
//    {
//        return $this->last_iterator_sql;
//    }
//
//    public function setFilter($sqlfilter)
//    {
//        $this->filter = $sqlfilter;
//        $this->sqlSelect->where = $sqlfilter;
//    }

    public function getCount(): int
    {

        $res = $this->db->query("SELECT count(*) as cnt from {$this->table} ");
        if (!$res) throw new Exception("Unable to get count: " . $this->db->getError());
        $row = $this->db->fetch($res);
        $this->db->free($res);
        return (int)$row["cnt"];

    }

    public function containsValue($key, $val)
    {

        $total = 0;
        $val = $this->db->escapeString($val);
        $sql = "SELECT SQL_CALC_FOUND_ROWS * from {$this->table} WHERE $key LIKE '$val' LIMIT 1";
        $itr = $this->createIterator($sql, $total);
        $this->db->free($itr);
        return ($total > 0);

    }

    public function haveField($field_name): bool
    {
        return in_array($field_name, $this->fields);
    }

//    public function startFieldIterator($filter_field, $filter_value)
//    {
//        return $this->startIterator("WHERE $filter_field='$filter_value'");
//    }
//
//    public function startSelectIterator(SQLSelect $select)
//    {
//        $total = -1;
//        $sql = $select->getSQL();
//
//        $this->db->free($this->iterator);
//
//        $this->iterator = $this->createIterator($sql, $total);
//
//        return $total;
//    }
//
//    public function createIteratorSQL($filter = "", $fields = " * ")
//    {
//        $itr_filter = $filter;
//
//        if ($this->filter) {
//            $itr_filter = trim($itr_filter);
//
//            $filter = str_ireplace("WHERE", "", $filter);
//            if (strpos($this->filter, "JOIN") !== FALSE) {
//                $itr_filter = $this->filter . " " . $filter;
//            }
//            else {
//                $itr_filter = "WHERE {$this->filter}" . $filter;
//            }
//        }
//
//        $sql = "SELECT SQL_CALC_FOUND_ROWS $fields FROM {$this->table} $itr_filter ";
//        return $sql;
//
//    }
//
//    public function createIterator($sql, &$total)
//    {
//        if ($this->iterator) $this->db->free($this->iterator);
//
//        $this->last_iterator_sql = $sql;
//
//        // 	debug("DBTabelBean::createIterator | SQL: $sql");
//
//        $itr = $this->db->query($sql);
//
//        if (!$itr) {
//
//            debug("DBTabelBean::createIterator | Unable to create iterator for SQL: $sql");
//
//            throw new Exception("Unable to create iterator: " . $this->db->getError());
//        }
//
//        $res = $this->db->query("SELECT FOUND_ROWS() as total");
//        $row = $this->db->fetch($res);
//        $this->db->free($res);
//
//        $total = (int)$row["total"];
//        return $itr;
//    }

    public function queryID(int $id)
    {
        return $this->queryField($this->prkey, $id, 1);
    }

    public function queryField(string $field, string $value, int $limit = 0, string $sign = " = ") : SQLQuery
    {
        $field = $this->db->escapeString($field);
        $value = $this->db->escapeString($value);

        $qry = $this->query();
        $qry->select->where = " $field $sign '$value' ";
        if ($limit>0) {
            $qry->select->limit = " $limit ";
        }
        return $qry;
    }

    public function query() : SQLQuery
    {
        $qry = new SQLQuery($this->select(), $this->prkey, $this->getTableName());
        $qry->setDB($this->db);
        return $qry;
    }

//    public function startIterator($filter = "", $fields = " * ")
//    {
//        $itr_filter = $filter;
//        if ($this->filter) {
//            $itr_filter = trim($itr_filter);
//
//            $filter = str_ireplace("WHERE", "", $filter);
//            if (strpos($this->filter, "JOIN") !== FALSE) {
//                $itr_filter = $this->filter . " " . $filter;
//            }
//            else {
//                $itr_filter = "WHERE {$this->filter}" . $filter;
//            }
//        }
//
//        $sql = "SELECT SQL_CALC_FOUND_ROWS $fields FROM {$this->table}  $itr_filter ";
//        $total = -1;
//
//        $this->iterator = $this->createIterator($sql, $total);
//
//        return $total;
//    }
//
//    public function fetchNext(array &$row, $iterator = FALSE): bool
//    {
//        if ($iterator === FALSE) {
//            $iterator = $this->iterator;
//        }
//
//        if ($row = $this->db->fetch($iterator)) {
//            return TRUE;
//        }
//        return FALSE;
//    }

    private function fillDebug()
    {
        ob_start();
        $usedby = debug_backtrace();
        print_r($usedby);
        $trace = ob_get_contents();
        ob_end_clean();
        return $trace;
    }

    public function getByID($id, $db = FALSE, $fields = " * ")
    {
        if (!$db) $db = $this->db;

        $select = clone $this->select();
        $select->fields = $fields;
        $select->where = " $this->prkey='$id' ";
        $select->limit = " 1 ";
        $qry = new SQLQuery($select, $this->prkey, $this->getTableName());
        $qry->setDB($db);

        $num = $qry->exec();

        if (! ($row = $qry->next())) {
            throw new Exception("No such ID");
        }

        return $row;
    }

    public function getByRef($refkey, $refid, $db = FALSE, $fields = " * ")
    {

        if (!$db) $db = $this->db;

        $refkey = $db->escapeString($refkey);
        $refid = (int)$refid;

        $select = clone $this->select();
        $select->fields = $fields;
        $select->where = " $refkey='$refid' ";
        $select->limit = " 1 ";

        $qry = new SQLQuery($select, $this->prkey, $this->getTableName());
        $qry->setDB($db);

        $num = $qry->exec();
        return $qry->next();

    }

    public function deleteID($id, $db = FALSE)
    {
        $docommit = FALSE;

        if (!$db) {
            $db = $this->db;
            $db->transaction();
            $docommit = TRUE;
        }

        $qry = "DELETE FROM {$this->table} WHERE {$this->prkey}=$id";

        $res = $db->query($qry);

        if (!$res) {
            if ($docommit) $db->rollback();
            throw new Exception("DBError: " . $db->getError());
        }

        if ($docommit) $db->commit();

        $this->manageCache($id);

        return $res;
    }

    public function deleteRef($refkey, $refval, $db = FALSE, $keep_ids = array())
    {
        $docommit = FALSE;
        if (!$db) {
            $db = $this->db;
            $db->transaction();
            $docommit = TRUE;
        }

        $sql = "DELETE FROM {$this->table} WHERE $refkey='$refval'";

        if (count($keep_ids) > 0) {
            $keep_list_ids = implode(",", $keep_ids);
            $sql .= " AND ({$this->prkey} NOT IN ($keep_list_ids)) ";
        }

        debug("DBTableBean::deleteRef: Executing SQL: $sql");

        $res = $db->query($sql);

        if (!$res) {
            if ($docommit) $db->rollback();
            throw new Exception("DBError: " . $db->getError());
        }

        if ($docommit) $db->commit();

        $this->manageCache($refval);

        return $res;
    }


    public function toggleField($id, $field)
    {
        if (!in_array($field, $this->fields)) throw new Exception("DBTableBean::toggleField Field '$field' not found in this bean");

        $id = (int)$id;


        $field = $this->db->escapeString($field);

        try {

            $this->db->transaction();

            if (!$this->db->query("UPDATE {$this->table} SET `$field` = NOT `$field` WHERE {$this->prkey}=$id ")) {
                throw new Exception("DBTableBean::toggleField DB Error: " . $this->db->getError());
            }

            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function findFieldValue($field_name, $field_value)
    {
        $qry = $this->queryField($field_name, $field_value, 1);
        $qry->exec();
        return $qry->next();
    }

    public function fieldValues(int $id, array $field_names) : ?array
    {
        $qry = $this->queryField($this->prkey, $id, 1);
        foreach ($field_names as $idx=>$value) {
            $field_names[$idx] = "`".$this->db->escapeString($value)."`";
        }
        $qry->select->fields = " {$this->prkey}, ".implode(",",$field_names);
        $qry->exec();
        if ($row = $qry->next()) {
            return $row;
        }
        return NULL;
    }

    public function fieldValue(int $id, string $field) : ?string
    {
        $field = $this->db->escapeString($field);

        $qry = $this->queryField($this->prkey, $id, 1);
        $qry->select->fields = " {$this->prkey}, `$field` ";
        $qry->exec();
        if ($row = $qry->next()) {
            return $row[$field];
        }
        return NULL;
    }

    public function needQuotes($key, &$value = "")
    {
        $storage_type = $this->storage_types[$key];
        // 	  if (strpos($storage_type,"char")!==false || strpos($storage_type,"text")!==false || strpos($storage_type,"blob")!==false  ||  strpos($storage_type,"enum")!==false) {
        // 		return true;
        // 	  }
        // 	  if (strpos($storage_type, "bool")!==false) {
        // 		return true;
        // 	  }
        if (strpos($storage_type, "date") !== FALSE || strpos($storage_type, "timestamp") !== FALSE) {
            if (endsWith($value, "()")) return FALSE;
            return TRUE;
        }
        return TRUE;
    }

    public function insert(&$row, &$db = FALSE)
    {

        $last_insert = -1;

        $docommit = FALSE;

        if (!$db) {
            $db = $this->db;
            $db->transaction();
            $docommit = TRUE;
        }

        $values = array();
        $this->prepareInsertValues($row, $values);

        $sql = "INSERT INTO {$this->table} (" . implode(",", array_keys($values)) . ") VALUES (" . implode(",", $values) . ")";

        if (defined("DEBUG_DBTABLEBEAN_DUMP_SQL")) {
            debug(get_class($this) . " INSERT SQL: $sql");
        }

        $ret = $db->query($sql);

        if ($ret === FALSE) {
            if ($docommit) $db->rollback();
            return -1;
        }

        //NOTE!!! lastID return the first auto_increment of a multi insert transaction
        $last_insert = $db->lastID();

        if ($docommit) $db->commit();

        $this->manageCache($last_insert);

        return $last_insert;
    }

    public function update($id, &$row, &$db = FALSE)
    {

        $docommit = FALSE;

        if (!$db) {
            $db = $this->db;
            $db->transaction();
            $docommit = TRUE;
        }

        $values = array();
        $this->prepareUpdateValues($row, $values);

        $sql = "UPDATE {$this->table} SET " . implode(",", $values) . " WHERE {$this->prkey}=$id";

        if (defined("DEBUG_DBTABLEBEAN_DUMP_SQL")) {
            debug(get_class($this) . " UPDATE SQL: $sql");
        }

        $ret = $db->query($sql);

        if ($ret === FALSE) {
            if ($docommit) $db->rollback();
            return FALSE;
        }

        if ($docommit) $db->commit();

        $this->manageCache($id);

        return $id;
    }

    protected function manageCache($id)
    {
        $cache_file = CACHE_ROOT . "/" . get_class($this) . "/" . $id;
        if (!is_dir($cache_file)) return;
        try {
            @deleteDir($cache_file);
        }
        catch (Exception $e) {
            //
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
            // 	  debug("Checking key='$key' : Value: ".$value. " STRLEN: ".strlen($value));

            if (is_array($value)) {

                if (count($value) < 1) continue;
                $value = $value[0];

            }

            if (is_null($value)) {
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

            if ($for_update === TRUE) {

                $values[$key] = "$key=" . $values[$key];//already quoted

            }
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

    public function getThumb($id, $width = 100)
    {
        $src = StorageItem::Image($id, get_class($this), $width, $width);
        return "<img src='$src'>";
    }
}

?>
