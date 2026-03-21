<?php
include_once("beans/DBTableBean.php");

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


    protected function getKeyID(string $key) : int
    {
        $this->assert_section();

        $query = $this->query($this->key(), "config_key", "section");
        $query->stmt->where()->add("section", $this->section);
        $query->stmt->where()->add("config_key", $key);
        $query->stmt->limit(1);
        $keyID = -1;
        if ($query->count() == 0) return $keyID;

        $query->exec();
        if ($result = $query->next()) {
            $keyID = (int)$result[$this->key()];
        }
        $query->free();

        return $keyID;
    }

    protected function getValueKey(string $key) : mixed
    {
        $this->assert_section();

        $query = $this->query($this->key(), "config_key", "config_val", "section");
        $query->stmt->where()->add("section", $this->section);
        $query->stmt->where()->add("config_key", $key);
        $query->stmt->limit(1);

        if ($query->count() == 0) return null;

        $value = null;
        $query->exec();
        if ($result = $query->next()) {
            $value = $result["config_val"];
        }
        $query->free();

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
        $this->assert_section();
        $this->deleteRef("config_key", $key);

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


    }

    /**
     * TODO remove form loading
     * @param InputForm $form
     * @return void
     */
    public function loadForm(InputForm $form) : void
    {
        foreach ($form->inputs() as $field_name => $field) {
            $stored_value = $this->get($field_name);
            $field->setValue($stored_value);
        }
    }
}