<?php

include_once ("lib/beans/DBTableBean.php");

abstract class OrderedDataBean extends DBTableBean
{

	
	private function appendFilter($have_other=true)
	{
		if ($this->filter) return ($have_other ? " AND {$this->filter} " : "{$this->filter}");
		return ($have_other ? "" : "1") ;
	}
	public function deleteID($id, $db=false){
        $docommit=false;
        if (!$db) {
            $db = DBDriver::factory();
            $db->transaction();
            $docommit=true;
        }
		$row = $this->getByID($id);
		$pos = (int)$row["position"];

		$res = $db->query("UPDATE {$this->table} SET position=position-1 WHERE position>$pos ".$this->appendFilter(true));

		//$res = $db->query("DELETE FROM {$this->table} WHERE {$this->prkey}=$id");	
		parent::deleteID($id, $db);

		if ($docommit==true) $db->commit();
		return $res;
	}
	
	public function insertRecord(&$row, &$db=false)
	{

		$pos = $this->getMaxPosition();
		$row["position"]=($pos+1);
		return parent::insertRecord($row, $db);

	}
	public function reorderFixed($id, $new_pos)
	{
		$db = DBDriver::factory();
		$db->transaction();

		$row = $this->getByID($id);
		$pos = (int)$new_pos;

		
		$resp = $db->query("UPDATE {$this->table}  SET position=position+1 WHERE position>=$pos ".$this->appendFilter());
		$ress = $db->query("UPDATE {$this->table}  SET position=$pos WHERE {$this->prkey}=$id");

		if ($resp && $ress){
			$db->commit();

		}
		else {
			$db->rollback();
		}
	}
  	public function reorderTop($id)
	{
		

		$db = DBDriver::factory();
		$db->transaction();

		$row = $this->getByID($id);
		$pos = (int)$row["position"];

		
		$resp = $db->query("UPDATE {$this->table}  SET position=position+1 WHERE position<$pos ".$this->appendFilter());
		$ress = $db->query("UPDATE {$this->table}  SET position=1 WHERE {$this->prkey}=$id");

		if ($resp && $ress){
			$db->commit();

		}
		else {
			$db->rollback();
		}
	}
	public function reorderBottom($id)
	{
		

		$db = DBDriver::factory();
		$db->transaction();
		$maxp = $this->getMaxPosition();

		$row = $this->getByID($id);
		$pos = (int)$row["position"];

		$resp = $db->query("UPDATE {$this->table}  SET position=position-1 WHERE position>$pos ".$this->appendFilter());
		$ress = $db->query("UPDATE {$this->table}  SET position=$maxp WHERE {$this->prkey}=$id");

		if ($resp && $ress){
			$db->commit();

		}
		else {
			$db->rollback();
		}
	}

	public function reorderUp($id)
	{
		

		$db = DBDriver::factory();
		$db->transaction();

		$row = $this->getByID($id);
		$pos = (int)$row["position"];

		$mx = $this->getMaxPosition();
		if ($pos-1 < 1) {
			//already at top
			return;
		}

		$resn = $db->query("UPDATE {$this->table}  SET position=-1 WHERE {$this->prkey}=$id ");
		$resp = $db->query("UPDATE {$this->table}  SET position=position+1 WHERE position=$pos-1 ".$this->appendFilter());
		$ress = $db->query("UPDATE {$this->table}  SET position=$pos-1 WHERE {$this->prkey}=$id ");

		if ($resp && $resn && $ress){
			$db->commit();

		}
		else {
			$db->rollback();
		}
	}
	public function reorderDown($id)
	{
		$db = DBDriver::factory();
		$db->transaction();

		$row = $this->getByID($id);
		$pos = (int)$row["position"];

		$mx = $this->getMaxPosition();
		if ($pos+1 > $mx) {
			//already at bottom
			return;
		}

		$resn = $db->query("UPDATE {$this->table} SET position=-1 WHERE {$this->prkey}=$id ");
		$resp = $db->query("UPDATE {$this->table} SET position=position-1 WHERE position=$pos+1 ".$this->appendFilter());
		$ress = $db->query("UPDATE {$this->table} SET position=$pos+1 WHERE {$this->prkey}=$id ");

		if ($resp && $resn && $ress){
			$db->commit();
		}
		else {
			$db->rollback();
		}
	}
	public function getMaxPosition(){
		$db = DBDriver::factory();
		$sql = "";

		$sql = "SELECT max(position)  FROM {$this->table} WHERE ".$this->appendFilter(false);

		$res = $db->query($sql);
		if (!$res) throw new Exception ("DBError: ".$db->getError()."<HR>$sql");
		$ret = $db->fetchRow($res);

		return (int)$ret[0];

	}
}
?>
