<?php
include_once ("lib/beans/DBTableBean.php");
include_once ("lib/utils/SelectQuery.php");

 class NestedSetBean extends DBTableBean
{

// CREATE TABLE `menu_items` (
//  `menuID` int(10) unsigned NOT NULL AUTO_INCREMENT,
//  `menu_title` varchar(255) NOT NULL,
//  `link` varchar(255) NOT NULL,
//  `parentID` int(10) unsigned NOT NULL DEFAULT '0',
//  `lft` int(10) unsigned NOT NULL,
//  `rgt` int(10) unsigned NOT NULL,
//  PRIMARY KEY (`menuID`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8

	public function __construct($table_name){
		parent::__construct($table_name);
	}
	
	public function insertRecord(&$row, &$db=false)
	{
		$lastid = -1;
		
		if (!$db) {
			$db = DBDriver::factory();
		}
		$prkey = $this->prkey;
		
		$parentID = (int)$row["parentID"];
		
		try {
			$db->transaction();
			
			if ($parentID>0) {
			
			  $parent_row = $this->getByID($parentID);
			  
			  $lft = $parent_row["lft"];
			  $rgt = $parent_row["rgt"];
			  $row["lft"]=$rgt;
			  $row["rgt"]=$rgt+1;
			  
			  $sql = "UPDATE {$this->table} SET rgt=rgt+2 WHERE rgt>=$rgt";
			  if ($this->filter) {
				  $sql.=" AND {$this->filter} ";
			  }
			  $res = $db->query($sql);
			  if (!$res)throw new Exception($db->getError());
			  
			  $sql = "UPDATE {$this->table} SET lft=lft+2 WHERE lft>$rgt";
			  if ($this->filter) {
				  $sql.=" AND {$this->filter} ";
			  }
			  $res = $db->query($sql);
			  if (!$res)throw new Exception($db->getError());
			  
			  $lastid = parent::insertRecord($row,$db);
					  
			}
			else {

			  $sql = "SELECT MAX(rgt) as max_rgt FROM {$this->table}";
			  if ($this->filter) {
				  $sql.=" AND {$this->filter} ";
			  }
			  $res = $db->query($sql);
			  if (!$res) throw new Exception($db->getError());
			  
			  if ($rr = $db->fetch($res)) {
				  $max_rgt = (int)$rr["max_rgt"];
				  $lft = $max_rgt+1;
				  $row["lft"] = $lft;
				  $row["rgt"] = $lft+1;
				  $lastid = parent::insertRecord($row, $db);
			  }

			}
			$db->commit();
		}
		catch (Exception $e) {
		  $db->rollback();
		  throw $e;
		}
		return $lastid;
	}
	
	public function insertRecord2(&$row, &$db=false)
	{
		$lastid = parent::insertRecord($row, $db);

		$this->reconstructNestedSet($db);

		return $lastid;
	}
	public function updateRecord($id, &$row, &$db=false)
	{

		if (!$db) {
			$db = DBDriver::factory();
		}
		$prkey = $this->prkey;

		$old_row = $this->getByID($id, $db);
		
		$old_parentID = (int)$old_row["parentID"];
		$new_parentID = (int)$row["parentID"];
		
		if ($new_parentID == $id) throw new Exception("Can not reparent to self");
		
		if ($old_parentID == $new_parentID) {
		
		  return parent::updateRecord($id, $row, $db);
		  
		}
		else {
		
		  $lastid = -1;
		  
		  try {
			$db->transaction();
			
			$parent_rgt = -1;
			if ($new_parentID>0) {
				$parent_row = $this->getByID($new_parentID, $db);
				$parent_rgt = $parent_row["rgt"];
			}
			else {
				//reparent to top
				$sql = "SELECT MAX(rgt) as max_rgt FROM {$this->table}";
				if ($this->filter) {
					$sql.=" AND {$this->filter} ";
				}
				$res = $db->query($sql);
				if (!$res) throw new Exception($db->getError());
				if ($parent_row = $db->fetch($res)) {
				  $parent_rgt = $parent_row["max_rgt"]+1;
				}
				
			}

			$lft = (int)$old_row["lft"];
			$rgt = (int)$old_row["rgt"];
			$width = $rgt - $lft;
			
			$new_lft = $parent_rgt;
			$new_rgt = $new_lft + $width;
			
			//width
			$extent = $width + 1;
			
			$distance = $new_lft - $lft;
			$tmppos = $lft;
			
			if ($distance < 0) {
				$distance -= $extent;
				$tmppos += $extent;
			}
			
			//make space
			$sql = "UPDATE {$this->table} SET lft = lft + $extent WHERE lft >= $new_lft";
			if ($this->filter) {
				$sql.=" AND {$this->filter} ";
			}
			$res = $db->query($sql);
			if (!$res)throw new Exception($db->getError());
			
			$sql = "UPDATE {$this->table} SET rgt = rgt + $extent WHERE rgt >= $new_lft";
			if ($this->filter) {
				$sql.=" AND {$this->filter} ";
			}
			$res = $db->query($sql);
			if (!$res)throw new Exception($db->getError());
			
			
			//move into new space
			$sql = "UPDATE {$this->table} SET lft = lft + $distance, rgt = rgt + $distance WHERE lft >= $tmppos AND rgt < $tmppos + $extent";
			if ($this->filter) {
				$sql.=" AND {$this->filter} ";
			}
			$res = $db->query($sql);
			if (!$res)throw new Exception($db->getError());
			
			
// 			//remove old space
			$sql = "UPDATE {$this->table} SET lft = lft - $extent WHERE lft > $rgt";
			if ($this->filter) {
				$sql.=" AND {$this->filter} ";
			}
			$res = $db->query($sql);
			if (!$res)throw new Exception($db->getError());
			
			$sql = "UPDATE {$this->table} SET rgt = rgt - $extent WHERE rgt > $rgt";
			if ($this->filter) {
				$sql.=" AND {$this->filter} ";
			}
			$res = $db->query($sql);
			if (!$res)throw new Exception($db->getError());
		  
			
			$lastid = parent::updateRecord($id, $row, $db);
			
			$db->commit();
			
			return $lastid;
		  }
		  catch (Exception $e) {
			$db->rollback();
			throw $e;
		  }
			
		}
		

		
	}
	public function deleteID($id, $db=false)
	{
		

		if (!$db) {
		    $db = DBDriver::factory();
		}

		$prow = $this->getByID($id, $db);

		$parentID = (int)$prow["parentID"];

		$lft = $prow["lft"];
		$rgt = $prow["rgt"];
		
		try {
			$db->transaction();
			
			$res = parent::deleteID($id, $db);
			if (!$res) throw new Exception($db->getError());
			
			
			$sql = "UPDATE {$this->table} SET lft=lft-1, rgt=rgt-1 WHERE lft BETWEEN $lft AND $rgt";
			if ($this->filter) {
				$sql.=" AND {$this->filter} ";
			}
			$res = $db->query($sql);
			if (!$res)throw new Exception($db->getError());
			
			
			$sql = "UPDATE {$this->table} SET rgt = rgt - 2 WHERE rgt > $rgt";
			if ($this->filter) {
				$sql.=" AND {$this->filter} ";
			}
			$res = $db->query($sql);
			if (!$res)throw new Exception($db->getError());
			
			$sql = "UPDATE {$this->table} SET lft = lft - 2 WHERE lft > $rgt";
			if ($this->filter) {
				$sql.=" AND {$this->filter} ";
			}
			$res = $db->query($sql);
			if (!$res)throw new Exception($db->getError());


			$sql = "UPDATE {$this->table} SET parentID=$parentID WHERE parentID=$id";
			if ($this->filter) {
			  $sql.=" AND {$this->filter} ";
			}
			$res = $db->query($sql);
			if (!$res)throw new Exception($db->getError());
			
			$db->commit();
		}
		catch (Exception $e) {
			$db->rollback();
			throw $e;
		}
		return $res;

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
$psel->where = " parentID=$parentID  ";
$psel->order_by = "  {$this->prkey} ASC , lft ASC ";
$sel = $sel->combineWith($psel);

		$res = $db->query($sel->getSQL());
		$num_rows = $db->fetchTotalRows();

		if ($num_rows){
		  while ($row=$db->fetch($res)){
			$catID = (int)$row[$prkey];
			$lft++;
			$db->transaction();

			$usql = "UPDATE {$this->table} set lft=$lft, rgt=$cnt WHERE $prkey=$catID ";
			
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

	public function constructPathField($catID, $field_name)
	{

	    $sqry = new SelectQuery();
	    $sqry->fields = " parent.catID, parent.$field_name ";
	    $sqry->from = " {$this->table} AS node, {$this->table} AS parent ";
	    $sqry->where = " (node.lft BETWEEN parent.lft AND parent.rgt) AND node.catID = $catID ";
	    $sqry->order_by = " parent.lft ";
	    
	    global $g_db;

	    $res = $g_db->query($sqry->getSQL());

	    if (!$res) throw new Exception("NestedSetBean::constructPath Error: ".$g_db->getError());

	    $path = array();
    
	    while ($row = $g_db->fetch($res)) {
		  $path[] = $row[$field_name];
	    }
	    
	    $g_db->free($res);

	    return $path;
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