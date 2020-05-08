<?php
include_once("beans/DBTableBean.php");

class ConfigBean extends DBTableBean
{

    protected $createString = "CREATE TABLE `config` (
 `cfgID` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `config_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 `config_val` longblob,
 `section` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
 PRIMARY KEY (`cfgID`),
 KEY `config_key` (`config_key`),
 KEY `section` (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
";

    private $vars = array();
    private static $instance = FALSE;

    protected $section = "";

    public function __construct()
    {
        parent::__construct("config");

    }


    public static function Factory()
    {
        if (self::$instance === FALSE) {
            self::$instance = new ConfigBean();
        }
        return self::$instance;
    }

    public function setSection($section)
    {
        $this->section = $section;
    }

    public function getValue(string $key, $def_value = "")
    {

        $key = DBConnections::Get()->escape($key);

        $sel = new SQLSelect();
        $sel->fields = "config_val";
        $sel->from = $this->table;
        $sel->where = "config_key='$key'";
        $sel->limit = " 1 ";

        if ($this->section) {
            $sel->where.= " AND section='{$this->section}' ";
        }

        $qry = new SQLQuery($sel);

        $result = $def_value;

        if ($qry->exec() && $row = $qry->next()) {
            $result = $row["config_val"];
            $serial = @unserialize($result);
            if ($serial instanceof StorageObject) {
                $result = $serial;
            }
        }


        return $result;
    }

    public function clearValue($key)
    {

        $db = DBConnections::Get();

        $s_key = $db->escape($key);

        $sql = "DELETE FROM {$this->table} WHERE config_key='$s_key' ";
        if ($this->section) {
            $sql .= " AND section='{$this->section}' ";
        }

        try {
            $db->transaction();
            $res = $db->query($sql);
            if (!$res) throw new Exception("Config::clearValue DELETE error:" . $db->getError());
            $db->commit();
        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function setValue($key, $val)
    {

        $db = DBConnections::Get();

        $s_key = $db->escape($key);

        $delete_sql = "DELETE FROM {$this->table} WHERE config_key='$s_key' ";
        if ($this->section) {
            $delete_sql .= " AND section='{$this->section}' ";
        }

        try {
            $db->transaction();

            $vals = array();
            if (!is_array($val)) {
                $db->query($delete_sql);
                $vals[] = $val;
            }
            else {
                foreach ($val as $pos => $value) {
                    $vals[] = $value;
                }
            }

            foreach ($vals as $pos => $value) {
                $row = array();
                $row["config_key"] = $s_key;

                if ($this->section) {
                    $row["section"] = $this->section;
                }

                if ($value instanceof StorageObject) {

                    if ($value->getUploadStatus() == 0) {

                        $row["config_val"] = $value->serializeDB();

                        $db->query($delete_sql);


                    }
                    else {
                        continue;
                    }

                }
                else {
                    $row["config_val"] = $db->escape($value);
                }

                $cfgID = $this->insert($row, $db);


                if ($cfgID < 1) throw new Exception("Config::setValue insert error:" . $db->getError());
            }

            $db->commit();

        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    }

    public function loadForm(InputForm $form)
    {
        foreach ($form->getInputs() as $field_name => $field) {
            $stored_value = $this->getValue($field_name);
            $field->setValue($stored_value);
        }
    }
}

?>
