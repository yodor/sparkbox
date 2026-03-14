<?php
include_once("dbdriver/DBDriver.php");
include_once("dbdriver/DBConnection.php");

class DBConnections
{

    protected static array $available_connections = array();

    protected static int $active_count = 0;
    protected static int $max_active_count = 0;

    public static function DriverEvent(DBDriverEvent $event): void
    {
        if ($event->isEvent(DBDriverEvent::OPENED)) {
            self::$active_count++;
            if (self::$active_count > self::$max_active_count) {
                self::$max_active_count = self::$active_count;
            }
            Debug::ErrorLog("Opened - active count: " . self::$active_count . " - max count: " . self::$max_active_count);
        } else if ($event->isEvent(DBDriverEvent::CLOSED)) {
            self::$active_count--;
            Debug::ErrorLog("Closed - active count: " . self::$active_count . " - max count: " . self::$max_active_count);
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

    protected static array $drivers = array();

    /**
     * Return reusable DBDriver for connection properties named '$conn_name' or DBConnection::DEFAULT_NAME if omitted.
     * DBDriver instance is reused on subsequent calls
     * Usually one global per app instance is needed. Remaining objects create additional connections where needed ie SQLQuery
     * @param string $conn_name
     * @return DBDriver
     * @throws Exception
     */
    public static function Driver(string $conn_name = DBConnection::DEFAULT_NAME): DBDriver
    {
        if (isset(self::$drivers[$conn_name])) {
            return self::$drivers[$conn_name];
        }

        try {
            $driver = self::CreateDriver($conn_name);
            self::$drivers[$conn_name] = $driver;
            return $driver;
        } catch (Exception $e) {
            Debug::ErrorLog("Error: ".$e->getMessage());
            throw $e;
        }

    }

    /**
     * Create driver using $conn_name properties and open connection
     * @param string $conn_name
     * @return DBDriver
     * @throws Exception
     */
    public static function CreateDriver(string $conn_name = DBConnection::DEFAULT_NAME): DBDriver
    {
        $props = self::Get($conn_name);

        $driver = null;

        switch ($props->driverClass) {
            case "MySQLi":
                include_once("dbdriver/MySQLiDriver.php");
                $driver = new MySQLiDriver($props);
            case "PDO":
                include_once("dbdriver/PDODriver.php");
                $driver = new PDODriver($props);
        }
        if (is_null($driver)) throw new Exception("Unsupported driver '{$props->driverClass}'");
        $driver->connect();
        return $driver;
    }

    public static function ActiveCount(): int
    {
        return self::$active_count;
    }

    public static function Count() : int
    {
        return count(self::$available_connections);
    }


}