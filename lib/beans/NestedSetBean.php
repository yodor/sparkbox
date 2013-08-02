<?php
include_once ("lib/beans/DBTableBean.php");
include_once ("lib/utils/SelectQuery.php");

 class NestedSetBean extends DBTableBean
{

	public function __construct($table_name){
		parent::__construct($table_name);
	}

	public function insertRecord(&$row, &$db=false)
	{
		$lastid = parent::insertRecord($row, $db);

		$this->reconstructNestedSet($db);

		return $lastid;
	}
	public function updateRecord($id, &$row, &$db=false)
	{

		$lastid = parent::updateRecord($id, $row, $db);

		$this->reconstructNestedSet($db);

		return $lastid;
	}

	public function reconstructNestedSet(&$db=false, &$lft=-1, &$cnt=0, $parentID=0 )
	{

		if (!$db) {
			$db = DBDriver::factory();
		}
		$prkey = $this->prkey;

$sel = $this->getSelectQuery();
$sel->fields="*";

$psel = new SelectQuery();
$psel->fields="";
$psel->where = " parentID=$parentID ";
$psel->order_by = " lft ";
$sel = $sel->combineWith($psel);

		$res = $db->query($sel->getSQL());
		$num_rows = $db->fetchTotalRows();

		if ($num_rows){
		  while ($row=$db->fetch($res)){
			$catID = (int)$row[$prkey];
			$lft++;
			$db->transaction();

			$usql = "UPDATE {$this->table} set lft=$lft, rgt=$cnt WHERE $prkey=$catID";
			$db->query($usql);
			$db->commit();
			$cnt++;
			$this->reconstructNestedSet($db,$lft,$cnt,$catID);

			$db->transaction();
			$db->query("UPDATE {$this->table} set rgt=$cnt WHERE $prkey=$catID");
			$db->commit();
			$lft=$cnt;
			$cnt++;
		  }
		}
	}

	protected function getIDLeft($lft)
	{



		$sql = "SELECT {$this->prkey} FROM {$this->table} WHERE lft = '$lft' ";
if ($this->filter) {
  $sql.=" AND {$this->filter} ";
}
		$res = $this->db->query($sql);

		if (!$res) throw new Exception("NestedSetBean::getIDLeft($lft) - Error: ".$this->db->getError());
		$row = $this->db->fetch($res);

		return $row[$this->prkey];
	}

	protected function getIDRight($rgt)
	{


		$sql = "SELECT {$this->prkey} FROM {$this->table} WHERE rgt = '$rgt' ";
if ($this->filter) {
  $sql.=" AND {$this->filter} ";
}
		$res = $this->db->query($sql);
		if (!$res) throw new Exception("NestedSetBean::getIDRight($rgt) - Error: ".$this->db->getError());
		$row = $this->db->fetch($res);

		return $row[$this->prkey];
	}


	public function moveLeft($id, $db=false)
	{
		if (!$db) $db = $this->db;

// 		$db->query("LOCK tables " . $this->table . " WRITE;");
		try {

			$db->transaction();

			$node = $this->getByID($id);

			$brotherId = $this->getIDRight($node["lft"]-1);

			if ($brotherId == false) {
				throw new Exception("Already at first position");
			}
			$brother = $this->getByID($brotherId);

			$nodeSize = $node["rgt"] - $node["lft"] + 1;
			$brotherSize = $brother["rgt"] - $brother["lft"] + 1;

if ($this->filter) {
  $sql.=" AND {$this->filter} ";
}

			$sql = "SELECT {$this->prkey} FROM {$this->table} WHERE   (lft BETWEEN {$node["lft"]} AND {$node["rgt"]}) ";

			$res = $db->query($sql);

			if (!$res) throw new Exception("NestedSetBean::moveLeft($id) - Error: ".$db->getError());

			$idlist = array();
			while ($row = $db->fetch($res)) {
				$idlist[] = $row[$this->prkey];
			}
			$idlist = implode(",",$idlist);

			$sql = "UPDATE {$this->table} SET lft = lft - $brotherSize, rgt = rgt - $brotherSize WHERE (lft BETWEEN {$node["lft"]} AND {$node["rgt"]}) ";
if ($this->filter) {
  $sql.=" AND {$this->filter} ";
}

			$res = $db->query($sql);
			if (!$res) throw new Exception("NestedSetBean::moveLeft($id) - Error: ".$db->getError());

			$sql = "UPDATE {$this->table} SET lft = lft + $nodeSize, rgt = rgt + $nodeSize WHERE (lft BETWEEN {$brother["lft"]} AND {$brother["rgt"]}) ";
			$sql.=" AND {$this->prkey} NOT IN ( $idlist ) ";
if ($this->filter) {
  $sql.=" AND {$this->filter} ";
}

			$res = $db->query($sql);
			if (!$res) throw new Exception("NestedSetBean::moveLeft($id) - Error: ".$db->getError());
			$db->commit();

		}
		catch (Exception $e) {
			$db->rollback();
			throw $e;
		}
// 		$db->query("UNLOCK TABLES;");

		return true;
	}
	public function moveRight($id, $db=false)
	{
		if (!$db) $db= $this->db;

// 		$db->query("LOCK tables " . $this->table . " WRITE;");
		try {

			$db->transaction();

			$node = $this->getByID($id);

			$brotherId = $this->getIDLeft($node["rgt"]+1);
			if ($brotherId == false) {
				throw new Exception("Already in last position");
			}
			$brother = $this->getByID($brotherId);

			$nodeSize = $node["rgt"] - $node["lft"] + 1;
			$brotherSize = $brother["rgt"] - $brother["lft"] + 1;



			$sql = "SELECT {$this->prkey} FROM {$this->table} WHERE  (lft BETWEEN {$node["lft"]} AND {$node["rgt"]}) ";
if ($this->filter) {
  $sql.=" AND {$this->filter} ";
}
			$res = $db->query($sql);
			if (!$res) throw new Exception("NestedSetBean::moveRight - unable to update table: ".$db->getError());

			$idlist = array();
			while ($row = $db->fetch($res)) {
				$idlist[] = $row[$this->prkey];
			}
			$idlist = implode(" , ", $idlist);

			$sql = "UPDATE {$this->table} SET lft = lft + $brotherSize, rgt = rgt + $brotherSize WHERE (lft BETWEEN {$node["lft"]} AND {$node["rgt"]}) ";
if ($this->filter) {
  $sql.=" AND {$this->filter} ";
}
			$res = $db->query($sql);
			if (!$res) throw new Exception("NestedSetBean::moveRight - unable to update table: ".$db->getError());

			$sql = "UPDATE {$this->table} SET lft = lft - $nodeSize, rgt = rgt - " . $nodeSize . " WHERE (lft BETWEEN " . $brother["lft"] . " AND " . $brother["rgt"].") ";
			$sql.= " AND {$this->prkey} NOT IN ($idlist) ";
if ($this->filter) {
  $sql.=" AND {$this->filter} ";
}

			$sql .= ";";
			$res = $db->query($sql);
			if (!$res) throw new Exception("NestedSetBean::moveRight - unable to update table: ".$db->getError());

			$db->commit();
		}
		catch (Exception $e) {
			$db->rollback();
			throw $e;
		}

		return true;
	}

	public function deleteID($id, $db=false)
	{
		$need_commit = false;

		if (!$db) {
		    $db = DBDriver::factory();
		    $db->transaction();
		    $need_commit = true;
		}

		$prow = $this->getByID($id);

		$parentID = (int)$prow["parentID"];

		$res = parent::deleteID($id,  $db);
		if (!$res)return $res;

		$sql = "UPDATE {$this->table} SET parentID=$parentID where parentID=$id";
if ($this->filter) {
  $sql.=" AND {$this->filter} ";
}
		$db->query($sql);

		if ($need_commit) {
			  $db->commit();
		}


		$this->reconstructNestedSet($db);

		return $res;

	}

	////
	public function parentCategories($catID, $field_name="")
	{


		$q = "SELECT parent.catID as catID, parent.category_name AS category_name FROM {$this->table} node, {$this->table} parent
	WHERE node.lft BETWEEN parent.lft AND parent.rgt
	AND node.catID = $catID
	AND parent.lft>0";

if ($this->filter) {
  $q.=" AND (node.{$this->filter} AND parent.{$this->filter}) ";
}

	$q.=" ORDER BY parent.lft; ";

		$res = $this->db->query($q);
		if (!$res) throw new Exception("NestedSetBean::parentCategories Error: ".$this->db->getError());

		$ret = array();
		while ($row=$this->db->fetch($res))
		{
			if (strlen($field_name)>0) {
				$ret[] = $row[$field_name];
			}
			else {
				$ret[]=$row;
			}
		}
		$this->db->free($res);

		return $ret;
	}

	
	public function constructPath($catID)
	{

	    $sqry = new SelectQuery();
	    $sqry->fields = " parent.catID ";
	    $sqry->from = " {$this->table} AS node, {$this->table} AS parent ";
	    $sqry->where = " (node.lft BETWEEN parent.lft AND parent.rgt) AND node.catID = $catID ";
	    $sqry->order_by = " parent.lft ";
	    
	    global $g_db;

	    $res = $g_db->query($sqry->getSQL());

	    if (!$res) throw new Exception("NestedSetBean::constructPath Error: ".$g_db->getError());

	    $path = array();
    
	    while ($row = $g_db->fetch($res)) {
		$path[] = $row["catID"];
	    }
	    
	    $g_db->free($res);

	    return $path;
	}

	public function listTreeRelatedSelect(DBTableBean $related_source)
	{		
	    $prkey = $this->prkey;

	    $fields = $related_source->getFields();

	    if (!in_array($prkey, $fields)) throw new Exception("Could not find relation by primary key with this related source bean");

	    $related_table =  $related_source->getTableName();
	    $related_prkey = $related_source->getPrKey();

	    //aggregate relation query
	    $sqry = new SelectQuery();
	    $sqry->fields = "  parent.*, COUNT( $related_table.$related_prkey ) as related_count ";
	    $sqry->from = "  {$this->table} AS node, {$this->table} AS parent, $related_table ";
	    $sqry->where = "  (node.lft BETWEEN parent.lft AND parent.rgt) AND node.$prkey = $related_table.$prkey ";
	    $sqry->group_by = " parent.$prkey ";
	    $sqry->order_by = " parent.lft ";
	    
	    return $sqry;

	}
	public function listTreeSelect()
	{
	    $sqry = new SelectQuery();
	    $sqry->fields = " node.* ";
	    $sqry->from = " {$this->table} AS node, {$this->table} AS parent ";
	    $sqry->where = " (node.lft BETWEEN parent.lft AND parent.rgt) ";
	    $sqry->group_by = " node.".$this->prkey;
	    $sqry->order_by = " node.lft ";
	    
	    return $sqry;
	}
	
	//used with aggregate table. selects node and its child nodes for aggregation
	public function childNodesWith(SelectQuery $other, $nodeID = -1)
	{
	    $pcsql = new SelectQuery();
	    $pcsql->fields = " child.* ";
	    $pcsql->from = " {$this->table} AS node , {$this->table} AS child ";
	    $pcsql->where = " (child.lft>= node.lft AND child.rgt <=node.rgt) ";
	    if ($nodeID>0) {
	      $pcsql->where.= " AND node.{$this->prkey} = $nodeID ";
	    }

	    return $pcsql->combineWith($other);
	}
	
	public function parentNodesWith(SelectQuery $other, $nodeID = -1)
	{
	    $pcsql = new SelectQuery();
	    $pcsql->fields = " parent.* ";
	    $pcsql->from = " {$this->table} AS node, {$this->table} AS parent ";
	    $pcsql->where = " (node.lft BETWEEN parent.lft AND parent.rgt) ";
	    if ($nodeID>0) {
	      $pcsql->where.= " AND node.{$this->prkey} = $nodeID ";
	    }
	    return $pcsql->combineWith($other);
	}

	public function childNodes($parentID)
	{
	    $pcsql = new SelectQuery();
	    $pcsql->fields = " * ";
	    $pcsql->from = " {$this->table} ";
	    $pcsql->where = " parentID='$parentID' ";
	    if ($this->filter) {
	      $pcsql->where.=" AND {$this->filter} ";
	    }
	    return $pcsql;
	}
}
?>