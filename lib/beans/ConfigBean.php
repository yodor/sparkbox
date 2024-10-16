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
        if (!(self::$instance instanceof ConfigBean)) {
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

    public function get(string $key, $default_value = "") : mixed
    {

        $key = DBConnections::Open()->escape($key);
        $section = DBConnections::Open()->escape($this->section);

        $qry = $this->queryField("config_key", $key, 1, "config_val");
        $qry->select->where()->add("section", "'$section'");
        $result = $default_value;

        if ($qry->exec() && $data = $qry->next()) {
            $result = $data["config_val"];
            $serial = @unserialize($result);
            if ($serial) {
                $result = $serial;
            }
        }

        return $result;
    }

    public function clear(string $key) : void
    {

        $this->deleteRef("config_key", $key);

    }

    public function set(string $key, $val) : void
    {

        $key = DBConnections::Open()->escape($key);

        $this->deleteRef("config_key", $key);

        if (is_object($val) || is_array($val)) {
            $val = serialize($val);
        }
        if (!is_null($val)) {
            $val = DBConnections::Open()->escape($val);
        }
        $section = DBConnections::Open()->escape($this->section);
        $data = array("config_key" => $key, "config_val" => $val, "section" => $section);
        $this->insert($data);

    }

    public function loadForm(InputForm $form)
    {
        foreach ($form->inputs() as $field_name => $field) {
            $stored_value = $this->get($field_name);
            $field->setValue($stored_value);
        }
    }
}

?>
