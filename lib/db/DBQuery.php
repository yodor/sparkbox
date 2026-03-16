<?php
include_once("dbdriver/IDBDriverAccess.php");

class DBQuery extends SparkObject
{
    /**
     * @var int last result INSERT/UPDATE/DELETE affectedRows
     */
    protected int $affectedRows = -1;

    /**
     * Current result
     * @var DBResult|null
     */
    protected ?DBResult $result = null;

    /**
     * @var DBDriver|null
     */
    private ?DBDriver $driver = null;

    public function __construct()
    {
        parent::__construct();

        $this->result = NULL;
        $this->driver = null;
    }

    public function __destruct()
    {
        $this->free();

    }

    public function free() : void
    {
        $this->affectedRows = -1;

        if ($this->result instanceof DBResult) {
            $this->result->free();
        }
        $this->result = NULL;

        //can support external driver using getDB/setDB
        if ($this->driver instanceof DBDriver) {
            if ($this->driver->getParent() === $this) {
                Debug::ErrorLog("Closing self created driver");
                $this->driver = null; //calls DTOR
            }
        }

    }

    /**
     *
     * Last result INSERT/UPDATE/DELETE affectedRows
     *
     * @return int
     */
    public function affectedRows() : int
    {
        return $this->affectedRows;
    }

    /**
     * Executes the provided statement and sets $this->affectedRows
     * Passing $db uses the passed driver, if this is select $db life should be ensured externally to this call
     * Does not set $this->driver unless it is self created driver and a select statement
     *
     * @param SQLStatement $statement Statement to execute
     * @throws Exception
     */
    public function exec(SQLStatement $statement, ?DBDriver $db=null): void
    {

        $this->free();

        //use provided or global from assignDriver or temp then it has parent to this
        $driver = $db ?? $this->assignDriver();

        try {

            // Execute query in unbuffered mode
            $result = $driver->query($statement);

            if (!$result->isActive()) {
                //store affected rows but not keep the driver - INSERT/DELETE/UPDATE
                $this->affectedRows = $driver->affectedRows();
            }
            else {
                //select statement store for further fetching from SelectQuery
                $this->result = $result;
                //keep driver if self created and is select
                if ($driver->getParent() === $this) {
                    $this->driver = $driver;
                }
            }

        } catch (Exception $e) {

            Debug::ErrorLog("Executing statement failed: " . $e->getMessage());
            $this->free();
            throw $e;
        }

    }


    /**
     * Return suitable driver either the global one or create new if the global is already processing query
     * return DBConnections::Driver() or DBConnections::CreateDriver($this->driver->getConnectionName())
     * If new driver is created $this->driver_created flag is set and using during free()
     *
     * @return DBDriver
     * @throws Exception
     */
    protected function assignDriver() : DBDriver
    {
        $driver = DBConnections::Driver();
        //already active result-set for fetching
        if ($driver->hasActiveResult()) {
            //unbuffered mode handling
            //SelectQuery::count() uses to open temp connection on blocked result-fetching
            $driver = DBConnections::CreateDriver($driver->getConnectionName());
            //own it
            $driver->setParent($this);
        }
        return $driver;
    }

}