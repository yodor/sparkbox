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
        $this->section = $section;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function get(string $key, mixed $default_value = "") : mixed
    {
        $ret = $default_value;

        $query = $this->queryField("config_key", $key, 1, "config_val");
        $query->select->where()->add("section", $this->section);

        $query->exec();

        if ($result = $query->nextResult()) {
            $ret = $result->get("config_val");
            $object = @unserialize($ret);
            if (is_object($object)) {
                $ret = $object;
            }
        }

        $query = null;

        return $ret;
    }

    public function clear(string $key) : void
    {

        $this->deleteRef("config_key", $key);

    }

    /**
     * @param string $key
     * @param string $val
     * @return void
     * @throws Exception
     */
    public function set(string $key, string|null $val) : void
    {

        try {

            $this->db->transaction();

            $this->deleteRef("config_key", $key, $this->db);

            $data = array("config_key" => $key, "config_val" => $val, "section" => $this->section);
            $this->insert($data, $this->db);

            $this->db->commit();
        }
        catch (Exception $e) {
            Debug::ErrorLog("Exception setting value for key='$key': ".$e->getMessage());
            $this->db->rollback();
            throw $e;
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
            if (is_object($stored_value)) {
                $field->setValue($stored_value);
            }
            else {
                $field->setValue(Spark::Unescape($stored_value));
            }
        }
    }
}