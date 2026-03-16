<?php
include_once("dbdriver/IDBDriverAccess.php");

class DBQuery implements IDBDriverAccess
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

    private bool $driver_created = false;

    public function __construct()
    {
        $this->result = NULL;
        $this->driver = NULL;
        $this->driver_created = false;
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

        //only close driver if it is created by '$this'
        if ($this->driver && $this->driver_created) {
           // Debug::ErrorLog("Closing created driver during exec call");
            $this->driver = null;
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
     *
     *
     * @param SQLStatement $statement Statement to execute
     * @throws Exception
     */
    public function exec(SQLStatement $statement): void
    {

        $this->free();

        $driver = $this->assignDriver();

        try {
            // Execute query in unbuffered mode
            $this->result = $driver->query($statement);

            if (!$this->result->isActive()) {
                //store affected rows but not keep the driver - INSERT/DELETE/UPDATE
                $this->affectedRows = $this->result->affectedRows();
            }
            else {
                //keep the driver for the active result to be fetched - SelectQuery
                $this->driver = $driver;
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
        if ($driver->hasResultSet()) {
            //unbuffered mode handling
            //Debug::ErrorLog("Driver has active result-set active. Creating new temporary driver.");
            //not kept anywhere else than during this call. SelectQuery uses this during the count() method
            $driver = DBConnections::CreateDriver($driver->getConnectionName());
            $this->driver_created = true;
        }
        return $driver;
    }

    /**
     * Return the current DBDriver
     * @return DBDriver|null
     */
    public function getDB(): ?DBDriver
    {
        return $this->driver;
    }

    /**
     * Set db for multi-exec usage in transaction style queries
     * @param DBDriver $driver
     * @return void
     */
    public function setDB(DBDriver $driver) : void
    {
        //TODO check external driver with assignDriver
        $this->driver = $driver;
    }



}