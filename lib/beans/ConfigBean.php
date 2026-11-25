<?php
include_once("beans/DBTableBean.php");
include_once("sql/RawSQLSelect.php");

class ConfigBean extends DBTableBean
{

    protected string $createString = "CREATE TABLE `config` (
 `cfgID` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `config_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 `config_val` longblob,
 `section` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
 PRIMARY KEY (`cfgID`),
 KEY `config_key` (`config_key`),
 KEY `section` (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
";

    private static ?ConfigBean $instance = null;

    protected string $section = "";

    public function __construct()
    {
        parent::__construct("config");
    }

    public static function Factory() : ConfigBean
    {
        if (is_null(self::$instance)) {
            self::$instance = new ConfigBean();
        }
        return self::$instance;
    }

    public function setSection(string $section) : void
    {
        $section = trim($section);
        if (strlen($section)==0) throw new Exception("Section name cannot be empty");

        $this->section = $section;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    protected function assert_section() : void
    {
        if (strlen($this->section)==0) throw new Exception("Section cannot be empty");
    }


    /**
     * The values of a section up to this size are loaded with the section, larger ones (an image) when asked for
     */
    const int INLINE_SIZE = 16384;

    /**
     * The keys of the sections read in this request (one query per section): section => key => [cfgID, value
     * (null: larger than INLINE_SIZE, not loaded yet)]
     * @var array<string, array<string, array>>
     */
    protected static array $sections = array();

    /**
     * The keys of the active section - loaded at its first use in the request
     * @return array<string, array>
     * @throws Exception
     */
    protected function sectionKeys() : array
    {
        $this->assert_section();
        if (isset(ConfigBean::$sections[$this->section])) return ConfigBean::$sections[$this->section];

        $select = new RawSQLSelect("SELECT cfgID, config_key, IF(LENGTH(config_val) <= " . ConfigBean::INLINE_SIZE
            . ", config_val, NULL) AS config_val, LENGTH(config_val) > " . ConfigBean::INLINE_SIZE . " AS large"
            . " FROM config WHERE section = :section ORDER BY cfgID ASC");
        $select->bind(":section", $this->section);
        $query = new SelectQuery($select, "cfgID", "config");
        $keys = array();
        $query->exec();
        while ($row = $query->next()) {
            //the first of a duplicate key, as the LIMIT 1 lookup gave
            if (isset($keys[$row["config_key"]])) continue;
            $keys[$row["config_key"]] = array((int)$row["cfgID"], ((int)$row["large"] > 0) ? null : $row["config_val"]);
        }
        $query->free();
        ConfigBean::$sections[$this->section] = $keys;
        return $keys;
    }

    protected function getKeyID(string $key) : int
    {
        $keys = $this->sectionKeys();
        return isset($keys[$key]) ? $keys[$key][0] : -1;
    }

    protected function getValueKey(string $key) : mixed
    {
        $keys = $this->sectionKeys();
        if (!isset($keys[$key])) return null;
        [$keyID, $value] = $keys[$key];
        if (!is_null($value)) return $value;

        //a large value - loaded now, once
        $row = $this->getByID($keyID, "config_val");
        $value = $row["config_val"] ?? "";
        ConfigBean::$sections[$this->section][$key][1] = $value;
        return $value;
    }

    public function get(string $key, mixed $default_value = "") : mixed
    {
        $this->assert_section();

        $value = $this->getValueKey($key);

        if (is_null($value)) {
            if (func_get_args()>1) {
                return $default_value;
            }
            return null;
        }

        if (is_string($value)) {
            $object = @unserialize($value);
            if (is_object($object)) {
                $value = $object;
            }
        }

        return $value;
    }

    public function clear(string $key) : void
    {
        //the key of this section only (other sections may have a key of the same name)
        $keyID = $this->getKeyID($key);
        if ($keyID > 0) $this->delete($keyID);
        unset(ConfigBean::$sections[$this->section]);

    }

    /**
     * Store value under key `$key`
     * Serializing ISerializable objects
     * @param string $key
     * @param ISerializable|string|float|int|bool|null $val
     * @return void
     * @throws Exception
     */
    public function set(string $key, ISerializable|string|float|int|bool|null $val, ?DBDriver $driver=null) : void
    {
        $this->assert_section();

        $keyID = $this->getKeyID($key);

        //Debug::ErrorLog("Setting value to key: $key using keyID: $keyID");

        $storeValue = $val;
        if ($val instanceof ISerializable) {
            Debug::ErrorLog("Serializing value of class: ".get_class($val));
            $storeValue = serialize($val);
        }

        $data = array("config_key" => $key, "config_val" => $storeValue, "section" => $this->section);
        if ($keyID > 0) {
            Debug::ErrorLog("Key[$keyID] exists - doing update");
            //do update
            $this->update($keyID, $data, $driver);
        }
        else {
            Debug::ErrorLog("Key ID not found - doing insert");
            //do insert
            $this->insert($data, $driver);
        }
        unset(ConfigBean::$sections[$this->section]);


    }

    /**
     * TODO remove form loading
     * @param InputForm $form
     * @return void
     */
    /**
     * The stored values into the fields of '$form' - a field never saved keeps the value the form gave it (its default)
     */
    public function loadForm(InputForm $form) : void
    {
        foreach ($form->inputs() as $field_name => $field) {
            $stored_value = $this->get($field_name, null);
            if (is_null($stored_value)) continue;
            $field->setValue($stored_value);
        }
    }
}