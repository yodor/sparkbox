<?php
/**
 * Storage upgrade - the stored photos and files (StorageObject, FileStorageObject, ImageStorageObject) of the format
 * before 'Serial' to the current format. Run it once on every site before the core without the old format support.
 *
 * Usage: php storage_upgrade.php <site root> [--list] [--write]
 *   <site root>  the folder with config/dbconfig.php (the database of the site)
 *   --list       every value to upgrade: table, key, class, the stored time (the old format kept the upload date
 *                as a date string - all of them are before 2024-08-24, when 'Serial' came), file name, data size
 *   --write      store the upgraded values - without it only counts (dry run)
 *
 * An old object without data (nothing to keep) is reported and left as it is.
 *
 * Every blob column of the database is scanned. A value with an old format object (no 'Serial' - protected property
 * names, a date string timestamp) is rebuilt in the current format: Serial, data, timestamp (unix time), uid,
 * filename (files and images), width, height (images). The rest of the value stays as it was (arrays of the config
 * table, other classes). Run it again after --write: it has to count 0.
 *
 * The tool does not load the core - it reads the database config with its own DBConfig / DBManager.
 */

if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit;
}

//the database config of the site --------------------------------------------------------------------------------
#[AllowDynamicProperties]
class DBConfig
{
    const string DEFAULT_NAME = "default";
    public string $driverClass = "PDO";
    public string $database = "";
    public string $user = "";
    public string $pass = "";
    public string $host = "127.0.0.1";
    public string $port = "3306";
    public string $name;

    public function __construct(string $connectionName = DBConfig::DEFAULT_NAME)
    {
        $this->name = $connectionName;
    }

    public function setVariables(array $arr) : void
    {
    }
}

class DBManager
{
    public static array $configs = array();

    public static function Add(DBConfig $config) : void
    {
        DBManager::$configs[$config->name] = $config;
    }
}

//the stored objects: the old format is read as it is, written in the current format ------------------------------
class StorageObject
{
    public array $values = array();
    public bool $old = false;

    public function __unserialize(array $data) : void
    {
        $this->old = !array_key_exists("Serial", $data);
        $values = array();
        foreach ($data as $key => $value) {
            //protected "\0*\0name" and private "\0Class\0name" property names
            $key = (string)$key;
            if (str_starts_with($key, "\0")) $key = substr($key, strrpos($key, "\0") + 1);
            $values[$key] = $value;
        }
        $this->values = $values;
        if (!$this->old) return;
        Upgrade::$found++;
        if (!isset($values["data"]) || !is_string($values["data"]) || $values["data"] === "") Upgrade::$empty++;
        Upgrade::$details[] = static::class . " time=" . (is_scalar($values["timestamp"] ?? null) ? $values["timestamp"] : "?")
            . " file=" . (is_scalar($values["filename"] ?? null) ? $values["filename"] : "-")
            . " data=" . strlen((string)($values["data"] ?? "")) . "B"
            . " keys=" . implode(",", array_keys($values));
    }

    public function __serialize() : array
    {
        if (!$this->old) {
            $result = $this->values;
            return $result;
        }
        $result = array();
        $result["Serial"] = 1.0;
        $result["data"] = (string)($this->values["data"] ?? "");
        $result["timestamp"] = Upgrade::Timestamp($this->values["timestamp"] ?? 0);
        $result["uid"] = (string)($this->values["uid"] ?? (microtime(true) . "." . rand()));
        return $result;
    }
}

class FileStorageObject extends StorageObject
{
    public function __serialize() : array
    {
        $result = parent::__serialize();
        if ($this->old) $result["filename"] = (string)($this->values["filename"] ?? "");
        return $result;
    }
}

class ImageStorageObject extends FileStorageObject
{
    public function __serialize() : array
    {
        $result = parent::__serialize();
        if ($this->old) {
            $result["width"] = (int)($this->values["width"] ?? 0);
            $result["height"] = (int)($this->values["height"] ?? 0);
        }
        return $result;
    }
}

class Upgrade
{
    const array CLASSES = array(StorageObject::class, FileStorageObject::class, ImageStorageObject::class);

    //old format objects in the value being read
    public static int $found = 0;
    //old format objects without data
    public static int $empty = 0;
    //what was found (--list)
    public static array $details = array();

    public static function Timestamp(mixed $value) : int
    {
        if (is_int($value)) return $value;
        $value = (string)$value;
        if (ctype_digit($value)) return (int)$value;
        $time = strtotime($value);
        return ($time === false) ? 0 : $time;
    }

    /**
     * The value in the current format - null when it has no old format object (or is not a serialized value)
     */
    public static function Value(string $stored) : ?string
    {
        Upgrade::$found = 0;
        Upgrade::$empty = 0;
        Upgrade::$details = array();
        $value = @unserialize($stored, array("allowed_classes" => Upgrade::CLASSES));
        if ($value === false && $stored !== serialize(false)) return null;
        if (Upgrade::$found < 1) return null;
        if (Upgrade::$empty > 0) throw new Exception("an old object without data - left as it is");
        $details = Upgrade::$details;

        $result = serialize($value);
        //the check: the new value reads back without an old object
        Upgrade::$found = 0;
        unserialize($result, array("allowed_classes" => Upgrade::CLASSES));
        if (Upgrade::$found > 0) throw new Exception("The upgraded value still has an old format object");
        Upgrade::$details = $details;
        return $result;
    }
}

//run --------------------------------------------------------------------------------------------------------------
$root = rtrim((string)($argv[1] ?? ""), "/");
$write = in_array("--write", $argv, true);
$list = in_array("--list", $argv, true);
if ($root === "" || !is_file($root . "/config/dbconfig.php")) {
    fwrite(STDERR, "Usage: php storage_upgrade.php <site root> [--write]\n");
    exit(1);
}
include($root . "/config/dbconfig.php");

foreach (DBManager::$configs as $name => $config) {
    echo "Database '{$config->database}' (connection '$name')" . ($write ? "" : " - dry run") . "\n";

    $pdo = new PDO("mysql:host={$config->host};port={$config->port};dbname={$config->database};charset=utf8mb4",
                   $config->user, $config->pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

    $columns = $pdo->prepare("SELECT c.TABLE_NAME, c.COLUMN_NAME, k.COLUMN_NAME AS KEY_NAME
        FROM information_schema.COLUMNS c
        JOIN information_schema.KEY_COLUMN_USAGE k ON k.TABLE_SCHEMA = c.TABLE_SCHEMA AND k.TABLE_NAME = c.TABLE_NAME
             AND k.CONSTRAINT_NAME = 'PRIMARY'
        WHERE c.TABLE_SCHEMA = ? AND c.DATA_TYPE IN ('blob', 'mediumblob', 'longblob', 'tinyblob', 'text', 'mediumtext', 'longtext')
        ORDER BY c.TABLE_NAME, c.COLUMN_NAME");
    $columns->execute(array($config->database));

    $total = 0;
    foreach ($columns->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $table = $column["TABLE_NAME"];
        $field = $column["COLUMN_NAME"];
        $key = $column["KEY_NAME"];

        //the candidates: a stored object of the storage classes without 'Serial'
        $ids = $pdo->query("SELECT `$key` FROM `$table` WHERE `$field` LIKE '%StorageObject\":%'
                            AND `$field` NOT LIKE '%s:6:\"Serial\";%'")->fetchAll(PDO::FETCH_COLUMN);
        //an old object can sit next to a new one (an array of the config table)
        $mixed = $pdo->query("SELECT `$key` FROM `$table` WHERE `$field` LIKE '%StorageObject\":%'
                            AND `$field` LIKE '%s:6:\"Serial\";%' AND `$field` LIKE '%s:7:\"\\0*\\0data\"%'")->fetchAll(PDO::FETCH_COLUMN);
        $ids = array_unique(array_merge($ids, $mixed));
        if (count($ids) < 1) continue;

        $read = $pdo->prepare("SELECT `$field` FROM `$table` WHERE `$key` = ?");
        $update = $pdo->prepare("UPDATE `$table` SET `$field` = ? WHERE `$key` = ?");
        $count = 0;
        foreach ($ids as $id) {
            $read->execute(array($id));
            $stored = $read->fetchColumn();
            $read->closeCursor();
            if (!is_string($stored)) continue;

            try {
                $value = Upgrade::Value($stored);
            }
            catch (Exception $e) {
                echo "  $table.$field $key=$id: skipped - " . $e->getMessage() . "\n";
                continue;
            }
            if (is_null($value)) continue;
            $count++;
            if ($list) {
                foreach (Upgrade::$details as $detail) echo "    $table $key=$id $detail\n";
            }
            if ($write) $update->execute(array($value, $id));
        }
        if ($count > 0) echo "  $table.$field: $count" . ($write ? " upgraded" : " to upgrade") . "\n";
        $total += $count;
    }
    echo "  total: $total" . ($write ? " upgraded" : " to upgrade") . "\n";
}
