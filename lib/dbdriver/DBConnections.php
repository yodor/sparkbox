<?php
include_once("dbdriver/DBDriver.php");
include_once("dbdriver/DBConnection.php");

class DBConnections
{

    protected static array $available_connections = array();

    protected static int $active_count = 0;


    public static function DriverEvent(DBDriverEvent $event): void
    {
        if ($event->isEvent(DBDriverEvent::OPENED)) {
            self::$active_count++;
            Debug::ErrorLog("Opened - active count: " . self::$active_count);
        } else if ($event->isEvent(DBDriverEvent::CLOSED)) {
            self::$active_count--;
            Debug::ErrorLog("Closed - active count: " . self::$active_count);
        }
    }

    /**
     * Add connection to this collection
     * @param DBConnection $dbconn
     * @return void
     */
    public static function Add(DBConnection $dbconn): void
    {
        self::$available_connections[$dbconn->getName()] = $dbconn;
    }

    public static function Get(string $connection_name): DBConnection
    {
        if (!self::Exists($connection_name)) throw new Exception("Undefined connection name '$connection_name'");
        return self::$available_connections[$connection_name];
    }

    /**
     * Return connection names
     * @return array
     */
    public static function Names(): array
    {
        return array_keys(self::$available_connections);
    }

    /**
     * Return true if named connection exists in this collection
     * @param string $connection_name
     * @return bool
     */
    public static function Exists(string $connection_name): bool
    {
        return array_key_exists($connection_name, self::$available_connections);
    }

    /**
     * Return DBDriver of the connection with name $conn_name or DBConnection::DEFAULT_NAME if omitted.
     * Ensures valid DBDriver connection is returned in connected state by checking the connection current state
     *
     * @param string $conn_name
     * @return DBDriver
     * @throws Exception
     */
    public static function Driver(string $conn_name = DBConnection::DEFAULT_NAME): DBDriver
    {
        try {
            $conn = self::Get($conn_name);
            $conn->open();
            $driver = $conn->driver();
            if (!is_null($driver)) return $driver;
            throw new Exception("DBConnection::driver() is null");
        } catch (Exception $e) {
            Debug::ErrorLog("Error: ".$e->getMessage());
            throw $e;
        }

    }

    public static function ActiveCount(): int
    {
        return self::$active_count;
    }

    public static function Count() : int
    {
        return count(self::$available_connections);
    }

    public static function CreateDriver(string $driverClass, DBConnection $conn): DBDriver
    {
        switch ($driverClass) {
            case "MySQLi":
                include_once("dbdriver/MySQLiDriver.php");
                return new MySQLiDriver($conn);
            case "PDO":
                include_once("dbdriver/PDODriver.php");
                return new PDODriver($conn);
        }
        throw new Exception("Unsupported driver '$driverClass'");
    }
}