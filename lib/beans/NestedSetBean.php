<?php
include_once("beans/DBTableBean.php");
include_once("sql/SQLSelect.php");
include_once("sql/SQLUpdate.php");

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

    public function __construct(string $table_name, ?DBDriver $dbdriver = NULL)
    {
        parent::__construct($table_name, $dbdriver);
        if (!$this->haveColumn("lft") || !$this->haveColumn("rgt") || !$this->haveColumn("parentID")) {
            throw new Exception("Incorrect table columns for NestedSetBean");
        }
    }

    public function insert(array $row, ?DBDriver $db = NULL): int
    {
        $lastid = -1;

        if (!$db) {
            $db = $this->db;
        }
        $prkey = $this->prkey;

        //defaults to the top level if not specified
        $parentID = 0;
        if (isset($row["parentID"])) {
            //use the parentID specified
            $parentID = (int)$row["parentID"];
        }
	
        try {
            $db->transaction();

            if ($parentID > 0) {

                $parent_row = $this->getByID($parentID, "lft", "rgt", "parentID");

                $lft = $parent_row["lft"];
                $rgt = $parent_row["rgt"];

                $row["lft"] = $rgt;
                $row["rgt"] = $rgt + 1;

                $update = new SQLUpdate($this->select);
                $update->set("rgt", "rgt+2");
                $update->where()->add(" rgt >= $rgt ", "", "");
                $db->query($update->getSQL());

                $update = new SQLUpdate($this->select);
                $update->set("lft", "lft+2");
                $update->where()->add(" lft > $rgt ", "", "");
                $db->query($update->getSQL());

                $lastid = parent::insert($row, $db);

            }
            else {

                $max_rgt = $this->getMaxRgt();
                $lft = $max_rgt + 1;
                $row["lft"] = $lft;
                $row["rgt"] = $lft + 1;
                $lastid = parent::insert($row, $db);

            }
            $db->commit();
        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        return $lastid;
    }

    public function getMaxRgt(): int
    {
        $qry = $this->query();
        $qry->select->fields()->setExpression(" MAX(rgt) ", "max_rgt");
        $qry->select->limit = " 1 ";
        $num = $qry->exec();

        if ($num < 1) throw new Exception($this->db->getError());

        $rr = $qry->next();
        return (int)$rr["max_rgt"];
    }

    public function insertRecord2(array $row, ?DBDriver $db = NULL): int
    {
        $lastid = parent::insert($row, $db);

        $this->reconstructNestedSet($db);

        return $lastid;
    }

    public function update(int $id, array $row, ?DBDriver $db = NULL) : int
    {

        if (!$db) {
            $db = $this->db;
        }

        $old_row = $this->getByID($id, "parentID", "lft", "rgt");

        $old_parentID = (int)$old_row["parentID"];
        $new_parentID = (int)$row["parentID"];

        if ($new_parentID == $id) throw new Exception("Can not re-parent to self");

        //check if is really a parent category
        $resultset = $this->getChildNodes($id, array($this->prkey));
        foreach ($resultset as $idx=>$result) {
            $nodeID = $result[$this->prkey];
            if ($new_parentID == $nodeID) {
                throw new Exception("Can not re-parent to child category");
            }
        }


        if ($old_parentID == $new_parentID) {

            return parent::update($id, $row, $db);

        }
        else {

            $lastid = -1;

            try {
                $db->transaction();

                $parent_rgt = -1;

                if ($new_parentID > 0) {
                    $parent_row = $this->getByID($new_parentID, "rgt");
                    $parent_rgt = $parent_row["rgt"];
                }
                else {
                    //reparent to top
                    $max_rgt = $this->getMaxRgt();
                    $parent_rgt = $max_rgt + 1;
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
                $update = new SQLUpdate($this->select);
                $update->set("lft", "lft + $extent");
                $update->where()->addExpression("lft >= $new_lft ");
                $db->query($update->getSQL());

                $update = new SQLUpdate($this->select);
                $update->set("rgt", "rgt + $extent");
                $update->where()->addExpression("rgt >= $new_lft");
                $db->query($update->getSQL());

                //move into new space
                $update = new SQLUpdate($this->select);
                $update->set("lft", "lft + $distance");
                $update->set("rgt", "rgt + $distance");
                $update->where()->addExpression(" (lft >= $tmppos AND rgt < $tmppos + $extent) ");
                $db->query($update->getSQL());

                //remove old space
                $update = new SQLUpdate($this->select);
                $update->set("lft", "lft - $extent");
                $update->where()->addExpression("lft > $rgt");
                $db->query($update->getSQL());

                $update = new SQLUpdate($this->select);
                $update->set("rgt", "rgt - $extent");
                $update->where()->addExpression("rgt > $rgt");
                $db->query($update->getSQL());

                $affectedRows = parent::update($id, $row, $db);

                $db->commit();

                return $affectedRows;
            }
            catch (Exception $e) {
                $db->rollback();
                throw $e;
            }

        }

    }

    public function delete(int $id, ?DBDriver $db = NULL): int
    {

        Debug::ErrorLog("Deleting ID: $id");

        $affectedRows = 0;

        if (!$db) {
            $db = $this->db;
            Debug::ErrorLog("Using local DBDriver instance");
        }
        else {
            Debug::ErrorLog("Using DBDriver passed as function parameter");
        }

        $prow = $this->getByID($id, "lft", "rgt", "parentID");

        $parentID = (int)$prow["parentID"];

        $lft = $prow["lft"];
        $rgt = $prow["rgt"];

        try {
            Debug::ErrorLog("Starting transaction");
            $db->transaction();

            $affectedRows = parent::delete($id, $db);

            Debug::ErrorLog("deleted node ID: $id");

            Debug::ErrorLog("Re-parenting child nodes ...");

            $update = new SQLUpdate($this->select);
            $update->set("lft", " lft - 1 ");
            $update->set("rgt", " rgt - 1 ");
            $update->where()->addExpression("(lft BETWEEN $lft AND $rgt)");
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("rgt", " rgt - 2 ");
            $update->where()->add("rgt > $rgt", "", "");
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("lft", " lft - 2 ");
            $update->where()->add(" lft > $rgt ", "", "");
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("parentID", $parentID);
            $update->where()->add("parentID", $id);
            $db->query($update->getSQL());

            $db->commit();
        }
        catch (Exception $ex) {
            Debug::ErrorLog("Rolling back for error: " . $ex->getMessage());
            $db->rollback();
            throw $ex;
        }

        return $affectedRows;

    }

    /**
     * @param $lft
     * @param $cnt
     * @param $parentID
     * @return void
     * @throws Exception
     */
    public function reconstructNestedSet(int &$lft = -1, int &$cnt = 0, int $parentID = 0) : void
    {

        $qry = $this->query();
        $qry->select->fields()->set($this->prkey, "lft", "rgt", "parentID");
        $qry->select->where()->add("parentID", $parentID);
        $qry->select->order_by = " $this->prkey ASC , lft ASC ";

        $num = $qry->exec();

        while ($row = $qry->next()) {
            $nodeID = (int)$row[$this->prkey];
            $lft++;

            $this->db->transaction();
            $update = new SQLUpdate($this->select);
            $update->set("lft", $lft);
            $update->set("rgt", $cnt);
            $update->where()->add($this->prkey, $nodeID);
            $this->db->query($update->getSQL());
            $this->db->commit();

            $cnt++;
            $this->reconstructNestedSet($lft, $cnt, $nodeID);

            $this->db->transaction();
            $update = new SQLUpdate($this->select);
            $update->set("rgt", $cnt);
            $update->where()->add($this->prkey, $nodeID);
            $this->db->query($update->getSQL());
            $this->db->commit();

            $lft = $cnt;
            $cnt++;
        }

    }

    protected function getIDLeft(int $lft): int
    {
        $qry = $this->query();
        $qry->select->fields()->set($this->prkey);
        $qry->select->limit = " 1 ";
        $qry->select->where()->add("lft", $lft);

        if ($qry->exec() && $row = $qry->next()) {
            return $row[$this->prkey];
        }
        throw new Exception("Unable to query: ".$qry->select->getSQL());
    }

    protected function getIDRight(int $rgt): int
    {
        $qry = $this->query();
        $qry->select->fields()->set($this->prkey);
        $qry->select->limit = " 1 ";
        $qry->select->where()->add("rgt", $rgt);

        if ($qry->exec() && $row = $qry->next()) {
            return (int)$row[$this->prkey];
        }
        throw new Exception("Unable to query: ".$qry->select->getSQL());

    }

    public function moveLeft(int $id, ?DBDriver $db = NULL) : void
    {
        if (!$db) $db = $this->db;

        try {

            $db->transaction();

            $node = $this->getByID($id, "lft", "rgt", "parentID");

            $brotherID = $this->getIDRight($node["lft"] - 1);

            if (!$brotherID) throw new Exception("Already at first position");

            $brother = $this->getByID($brotherID, "lft", "rgt", "parentID");

            $nodeSize = (int)$node["rgt"] - (int)$node["lft"] + 1;
            $brotherSize = (int)$brother["rgt"] - (int)$brother["lft"] + 1;

            $qry = $this->query();
            $qry->select->fields()->set($this->prkey);
            $qry->select->where()->addExpression("(lft BETWEEN " . $node["lft"] . " AND " . $node["rgt"] . ")");
            $num = $qry->exec();

            $idlist = array();
            while ($row = $qry->next()) {
                $idlist[] = $row[$this->prkey];
            }
            $idlist = implode(",", $idlist);

            $update = new SQLUpdate($this->select);
            $update->set("lft", " lft - $brotherSize ");
            $update->set("rgt", " rgt - $brotherSize ");
            $update->where()->addExpression(" (lft BETWEEN " . $node["lft"] . " AND " . $node["rgt"] . ") ");
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("lft", " lft + $nodeSize ");
            $update->set("rgt", " rgt + $nodeSize ");
            $update->where()->addExpression(" (lft BETWEEN " . $brother["lft"] . " AND " . $brother["rgt"] . ") ");
            $update->where()->add($this->prkey, " ( $idlist ) ", "NOT IN");
            $db->query($update->getSQL());

            $db->commit();

        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    }

    public function moveRight(int $id, ?DBDriver $db = NULL) : void
    {
        if (!$db) $db = $this->db;

        try {

            $db->transaction();

            $node = $this->getByID($id, "lft", "rgt", "parentID");

            $brotherId = $this->getIDLeft($node["rgt"] + 1);
            if (!$brotherId) throw new Exception("Already in last position");

            $brother = $this->getByID($brotherId, "lft", "rgt", "parentID");

            $nodeSize = (int)$node["rgt"] - (int)$node["lft"] + 1;
            $brotherSize = (int)$brother["rgt"] - (int)$brother["lft"] + 1;

            $qry = $this->query();
            $qry->select->fields()->set($this->prkey);
            $qry->select->where()->addExpression("( lft  BETWEEN " . $node["lft"] . " AND " . $node["rgt"] . ") ");

            $qry->exec();
            $idlist = array();
            while ($row = $qry->next()) {
                $idlist[] = $row[$this->prkey];
            }
            $idlist = implode(" , ", $idlist);

            $update = new SQLUpdate($this->select);
            $update->set("lft", " lft + $brotherSize ");
            $update->set("rgt", " rgt + $brotherSize ");
            $update->where()->addExpression("( lft BETWEEN " . $node["lft"] . " AND " . $node["rgt"] . ") ");
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("lft", " lft - $nodeSize ");
            $update->set("rgt", " rgt - $nodeSize ");
            $update->where()->addExpression("( lft BETWEEN " . $brother["lft"] . " AND " . $brother["rgt"] . ") ");
            $update->where()->add($this->prkey, "($idlist)", "NOT IN");
            $db->query($update->getSQL());

            $db->commit();
        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    }

    /**
     * When doing simple tree list use $prefix='node'
     * When doing aggregate tree list use $prefix='parent'
     * @param array $columns Columns to select from this bean prefixed with '$prefix.'
     * @param string $prefix 'node' or 'parent'
     * @return SQLSelect
     * @throws Exception
     */
    public function selectTree(array $columns = array(), string $prefix = "node"): SQLSelect
    {
        if (strcmp($prefix, "node") != 0 && strcmp($prefix, "parent") != 0) {
            throw new Exception("Prefix should be 'node' or 'parent'");
        }

        $prkey = $this->prkey;

        $fields = array("$prefix.$prkey", "$prefix.lft", "$prefix.rgt");

        foreach ($columns as $idx => $field) {
            $fields[] = "$prefix.$field";
        }

        $sel = new SQLSelect();

        $sel->fields()->set(...$fields);

        $sel->from = " $this->table AS node, $this->table AS parent ";
        $sel->where()->addExpression("( node.lft BETWEEN parent.lft AND parent.rgt )");

        $this->select->where()->copyTo($sel->where());

        $sel->group_by = " $prefix." . $this->prkey;
        $sel->order_by = " $prefix.lft ";

        return $sel;
    }

    /**
     * @param string $nodeEquals
     * @param array $fieldNames
     * @return SQLSelect
     * @throws Exception
     */
    public function selectAggregated(string $nodeEquals, array $fieldNames = array()): SQLSelect
    {
        $prkey = $this->prkey;

        //select the parent.prkey and lft/rgt
        //add where clause
        $sel = $this->selectTree($fieldNames, "parent");

        $sel->where()->add("node.$prkey", "$nodeEquals");

       // $sel->group_by = " ";

        //$sel->order_by = " node.lft ";

        return $sel;
    }

    /**
     * Aggregate the tree select counting items from '$relation_table' contained in each branch of the tree
     * '$relation_table' should have column name equal to this bean prkey
     * The count is returned in column named 'related_count'
     * '$relation' is cloned first
     * @param SQLSelect $relation The complete select for the other table that will be combined with the tree select
     * @param string $relation_table DB table name used in '$relation'
     * @param string $relation_prkey DB table primary key used in '$relation'
     * @param array $columns Columns to be selected from this bean
     * @return SQLSelect
     */
    public function selectTreeRelation(SQLSelect $relation, string $relation_table, string $relation_prkey, array $columns = array(), bool $with_count = true) : SQLSelect
    {
        //relation SQLSelect might have WHERE clauses filled when search is applied to products first we then count the
        //results after the $relation is filtered
        $result = clone $relation;
        //reset the fields but keep the where clauses
        //resulting select is only for drawing the tree
        $result->fields()->reset();

        $prkey = $this->prkey;

        $sel = $this->selectAggregated("$relation_table.$prkey", $columns);

        if ($with_count) {
            $sel->fields()->setExpression("COUNT($relation_table.$relation_prkey)", "related_count");
        }
        //$sel->group_by = " parent.$prkey ";

        return $sel->combineWith($result);

    }

    /**
     * Used to do table aggregation.
     * Selects node and its child nodes for aggregation
     * @param SQLSelect $other
     * @param string $relation_table
     * @param int $nodeID
     * @param array $columns
     * @return SQLSelect
     */
    public function selectChildNodesWith(SQLSelect $other, string $relation_table, int $nodeID = -1, array $columns = array()): SQLSelect
    {
        //other 'from' should be selected as TABLE as relation
        $prkey = $this->prkey;

        $prefix = "child";

        $fields = array();

        //prepend primary key to columns
        if (!in_array($prkey, $columns)) {
            array_unshift($columns, $prkey);
        }

        //prepend prefix to column names
        foreach ($columns as $idx => $field) {
            //if (strcmp($field, $prkey)==0) {
            //    $fields[] = "DISTINCT $prefix.$field";
            //}
            //else {
                $fields[] = "$prefix.$field";
            //}
        }

        $sel = new SQLSelect();

        $sel->fields()->set(...$fields);
        //

        $sel->from = " $this->table AS node , $this->table AS child ";

        $sel->where()->addExpression("(child.lft BETWEEN node.lft AND node.rgt)");

        $sel->where()->add("$relation_table.$prkey", "child.$prkey");

        if ($nodeID > 0) {
            $sel->where()->add("node.$prkey", $nodeID, " = ");
        }
        else {
            $sel->where()->add("node.parentID", 0, " = ");
        }

        $this->select->where()->copyTo($sel->where());

        return $sel->combineWith($other);
    }

    public function childNodes(int $parentID): SQLSelect
    {
        $sel = clone $this->select();
        $sel->where()->add("parentID", "'$parentID'");

        return $sel;
    }

    /**
     * Query nodeID and its parent nodes
     * @param int $nodeID
     * @param array $fieldNames
     * @return array
     * @throws Exception
     */
    public function getParentNodes(int $nodeID, array $fieldNames = array()): array
    {
        $prkey = $this->prkey;

        $sel = $this->selectAggregated($nodeID, $fieldNames);

        $qry = new SQLQuery($sel, $this->prkey, $this->table);
        $qry->exec();

        $ret = array();
        while ($row = $qry->next()) {
            $ret[] = $row;
        }
        return $ret;
    }

    /**
     * Query nodeID and its child nodes
     * @param int $nodeID
     * @param array|null $fieldNames
     * @return array
     * @throws Exception
     */
    public function getChildNodes(int $nodeID, ?array $fieldNames = null) : array
    {
        if (is_null($fieldNames)) {
            $fieldNames = array($this->prkey);
        }

        $sel = $this->selectTree($fieldNames);
        $sel->where()->add("parent.".$this->prkey, $nodeID);

        $resultset = array();

        $qry = new SQLQuery($sel, $this->prkey, $this->table);
        $num = $qry->exec();
        while ($result = $qry->next()) {
            $resultset[] = $result;
        }

        return $resultset;
    }

}

?>
