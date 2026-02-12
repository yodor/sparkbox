<?php
include_once("objects/SparkObject.php");
include_once("dbdriver/DBDriver.php");
include_once("dbdriver/DBConnection.php");

class DBConnections extends SparkObject
{

    protected static array $available_connections = array();

    protected static int $active_count = 0;

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
     * Return connection to db using connection name $conn_name if not specified the default connection name is used
     * If connection is not open a call to open will be made as to ensure valid DBDriver connection is returned in connected state
     * @param string $conn_name
     * @return DBDriver
     * @throws Exception
     */
    public static function Open(string $conn_name = DBConnection::DEFAULT_NAME): DBDriver
    {
        try {
            $conn = self::Get($conn_name);
            if (!$conn->isOpen()) {
                $conn->open();
            }
            $driver = $conn->get();
            if ($driver instanceof DBDriver) return $driver;
            throw new Exception("Unable to get valid connection to database using connection name '$conn_name'");
        } catch (Exception $e) {
            Debug::ErrorLog("Unable to open connection with '$conn_name'");
            throw $e;
        }

    }

    public static function Count(): int
    {
        return self::$active_count;
    }

    public static function connectionEvent(SparkEvent $event): void
    {
        if (!($event instanceof DBDriverEvent)) return;

        if ($event->isEvent(DBDriverEvent::OPENED)) {
            self::$active_count++;
            Debug::ErrorLog("Opened - active count: " . self::$active_count);
        } else if ($event->isEvent(DBDriverEvent::CLOSED)) {
            self::$active_count--;
            Debug::ErrorLog("Closed - active count: " . self::$active_count);
        }


    }
}