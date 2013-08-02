<?php
include_once("lib/beans/DBTableBean.php");

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
	private static $instance=FALSE;
	
	protected $section = "";

	public  function __construct()
	{	
		parent::__construct("config");
		
	}

	
	public static function factory()
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

	public function getValue($key, $def_value="")
	{
		
		$s_key = $this->db->escapeString($key);

		$sql = "SELECT config_val FROM {$this->table} WHERE config_key='$s_key'";
		if ($this->section) {
			$sql.= " AND section='{$this->section}' ";
		}

		$res = $this->db->query($sql);
		if (!$res)throw new Exception("Config::getValue SELECT error: ".$this->db->getError());

		$num_rows = $this->db->fetchTotalRows();

		$result = $def_value;

		while ($row=$this->db->fetch($res))
		{
			$val = $row["config_val"];
			$serial = @unserialize($val);
			if ($serial instanceof StorageObject) {
				$val = $serial;
			}
			if ($num_rows<2) return $val;

			$result = $val;
			
		}
		return $result;
	}
    public function clearValue($key)
    {

        $s_key = $this->db->escapeString($key);

		$sql = "DELETE FROM {$this->table} WHERE config_key='$s_key' ";
		if ($this->section) {
			$sql.= " AND section='{$this->section}' ";
		}

		try {
			$this->db->transaction();
			$res = $this->db->query($sql);
			if (!$res) throw new Exception("Config::clearValue DELETE error:".$this->db->getError());
			$this->db->commit();
		}
		catch (Exception $e)
		{
			$this->db->rollback();
			throw $e;
		}
    }
	public function setValue($key,$val)
	{

		$s_key = $this->db->escapeString($key);

		$delete_sql = "DELETE FROM {$this->table} WHERE config_key='$s_key' ";
		if ($this->section) {
			$delete_sql.= " AND section='{$this->section}' ";
		}

		try {
			$this->db->transaction();

			$vals = array();
			if (!is_array($val)) {
				$this->db->query($delete_sql);
				$vals[] = $val;
			}
			else {
				foreach ($val as $pos=>$value){
				  $vals[] = $value;
				}
			}

		  foreach($vals as $pos=>$value) {
			  $row = array();
			  $row["config_key"] = $s_key;

			  if ($this->section) {
				$row["section"]=$this->section;
			  }

			  if ($value instanceof StorageObject) {

				if ($value->getUploadStatus() == 0) {

					$row["config_val"] = $value->serializeDB();

					$this->db->query($delete_sql);


				}
				else {
					continue;
				}

			  }
			  else {
				  $row["config_val"] = $this->db->escapeString($value);
			  }

			  $cfgID = $this->insertRecord($row, $this->db);
			  

			  if ($cfgID<1) throw new Exception("Config::setValue insert error:".$this->db->getError());
		  }

		  $this->db->commit();	
			
		}
		catch (Exception $e) {
			$this->db->rollback();
			throw $e;
		}
		
	}
	public function loadForm(InputForm $form)
	{
		foreach($form->getFields() as $field_name=>$field) {
		  $stored_value = $this->getValue($field_name);
		  $field->setValue($stored_value);
		}
	}
}

?>
