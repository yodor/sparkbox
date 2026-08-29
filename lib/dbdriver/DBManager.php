<?php
include_once("dbdriver/DBDriver.php");
include_once("dbdriver/DBConfig.php");

class DBManager
{
    public const int MAX_DRIVER_POOL_SIZE = 4;

    /**
     * Map holding application-defined DB configurations indexed by name.
     * @var array<string, DBConfig>
     */
    protected static array $dsn = array();

    /**
     * Current active connections count.
     * @var int
     */
    protected static int $active_count = 0;

    /**
     * Maximum active connections count reached.
     * @var int
     */
    protected static int $max_active_count = 0;

    /**
     * Map of active database drivers indexed by connection configuration name.
     * @var array<string, array<int, DBDriver>>
     */
    protected static array $drivers = array();

    public static function DriverEvent(DBDriverEvent $event): void
    {
        $source = $event->getSource();
        $isPersistent = "Unknown";
        if ($source instanceof DBDriver) {
            $isPersistent = "IsPersistent = [" . ($source->isPersistent() ? 'true' : 'false') . "]";
        }

        if ($event->isEvent(DBDriverEvent::OPENED)) {
            self::$active_count++;
            if (self::$active_count > self::$max_active_count) {
                self::$max_active_count = self::$active_count;
            }

            Debug::ErrorLog("Opened ($isPersistent) - active count: " . self::$active_count . " - max count: " . self::$max_active_count);
        } else if ($event->isEvent(DBDriverEvent::CLOSED)) {
            self::$active_count--;
            Debug::ErrorLog("Closed ($isPersistent) - active count: " . self::$active_count . " - max count: " . self::$max_active_count);
        }
    }

    /**
     * Add DBConfig DSN to the collection for later use.
     *
     * @param DBConfig $config
     * @return void
     */
    public static function Add(DBConfig $config): void
    {
        self::$dsn[$config->getName()] = $config;
    }

    /**
     * Check if a DBConfig named '$config_name' exists.
     *
     * @param string $config_name
     * @return bool
     */
    public static function Exists(string $config_name): bool
    {
        return isset(self::$dsn[$config_name]);
    }

    /**
     * Retrieve defined DBConfig named '$config_name'.
     *
     * @param string $config_name
     * @return DBConfig
     * @throws Exception If configuration name is undefined.
     */
    public static function Get(string $config_name): DBConfig
    {
        if (!self::Exists($config_name)) {
            throw new Exception("Undefined connection configuration name '$config_name'");
        }
        return self::$dsn[$config_name];
    }

    /**
     * Return the count of defined DBConfigs.
     *
     * @return int
     */
    public static function Count(): int
    {
        return count(self::$dsn);
    }

    /**
     * Return all defined configuration names.
     *
     * @return array<string>
     */
    public static function Names(): array
    {
        return array_keys(self::$dsn);
    }

    /**
     * Return an available non-busy DBDriver matching the requested persistence mode.
     *
     * @param string $conn_name
     * @param bool $persistent
     * @return DBDriver
     * @throws Exception
     */
    public static function Driver(string $connName = DBConfig::DEFAULT_NAME, bool $persistent = false): DBDriver
    {
        if (!isset(self::$drivers[$connName])) {
            self::$drivers[$connName] = array();
        }

        $availableDriver = null;

        foreach (self::$drivers[$connName] as $idx => $driver) {
            // Phase 1: Skip drivers busy with un-fetched result sets
            if ($driver->hasActiveResult()) {
                Debug::ErrorLog("DBDriver index[$idx] is busy with active result set");
                continue;
            }

            // Phase 2: Skip drivers with mismatched persistence modes
            if ($driver->isPersistent() !== $persistent) {
                Debug::ErrorLog("DBDriver index[$idx] persistence mismatch (requested: " . ($persistent ? 'true' : 'false') . ", actual: " . ($driver->isPersistent() ? 'true' : 'false') . ")");
                continue;
            }

            // Phase 3: Driver is idle and matches requirements - test connection
            Debug::ErrorLog("Candidate DBDriver found at index[$idx]");

            if ($driver->isConnected()) {
                $availableDriver = $driver;
                break;
            }

            // Connection dropped - attempt to reconnect
            try {
                Debug::ErrorLog("Restoring connection for driver index[$idx]...");
                $driver->connect($persistent);
                $availableDriver = $driver;
                break;
            } catch (Exception $E) {
                Debug::ErrorLog("Failed restoring driver index[$idx]: " . $E->getMessage());
                // Unset dead driver slot so pool size stays accurate for fallback instantiation
                unset(self::$drivers[$connName][$idx]);
                self::$drivers[$connName] = array_values(self::$drivers[$connName]);
                $availableDriver = null;
            }
        }

        // Fallback: Create and pool a new driver instance if no candidate succeeded
        if (is_null($availableDriver)) {
            $poolSize = count(self::$drivers[$connName]);
            Debug::ErrorLog("No reusable driver found. Pool size [$poolSize/" . self::MAX_DRIVER_POOL_SIZE . "]. Creating new instance...");

            if ($poolSize >= self::MAX_DRIVER_POOL_SIZE) {
                throw new Exception("DBDriver pool limit (" . self::MAX_DRIVER_POOL_SIZE . ") reached for '$connName'");
            }

            $availableDriver = self::CreateDriver($connName, $persistent);
            self::$drivers[$connName][] = $availableDriver;
        }

        return $availableDriver;
    }

    /**
     * Create and return a connected DBDriver instance.
     *
     * @param string $conn_name
     * @param bool $persistent
     * @return DBDriver
     * @throws Exception
     */
    private static function CreateDriver(string $conn_name = DBConfig::DEFAULT_NAME, bool $persistent = false): DBDriver
    {
        $props = self::Get($conn_name);
        $driver = null;

        switch ($props->driverClass) {
            case "PDO":
                include_once("dbdriver/PDODriver.php");
                $driver = new PDODriver($props);
                break;
        }

        if (is_null($driver)) {
            throw new Exception("Unsupported driver '{$props->driverClass}'");
        }

        $driver->connect($persistent);
        return $driver;
    }
}