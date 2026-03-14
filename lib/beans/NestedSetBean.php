<?php
include_once("beans/DBTableBean.php");
include_once("sql/SQLSelect.php");
include_once("sql/SQLUpdate.php");

class Node {

    protected int $parentID = -1;
    protected int $lft = -1;
    protected int $rgt = -1;
    protected int $size = -1;
    protected int $id = -1;

    public function __construct(int $id, array $data) {
        $this->lft = (int)$data['lft'];
        $this->rgt = (int)$data['rgt'];
        $this->size = $this->rgt  - $this->lft + 1;
        $this->parentID = $data['parentID'];
        $this->id = $id;
    }
    public function id() : int
    {
        return $this->id;
    }
    public function parentID() : int
    {
        return $this->parentID;
    }
    public function lft() : int
    {
        return $this->lft;
    }
    public function rgt() : int
    {
        return $this->rgt;
    }
    public function size() : int
    {
        return $this->size;
    }
}

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

    protected function getMaxRgt(): int
    {
        $select = new SQLSelect();
        $select->from = $this->table;
        $select->fields()->setAliasExpression(" MAX(rgt) ", "max_rgt");
        $select->limit = " 1 ";
        $query = new SQLQuery($select);
        $query->exec();

        if ($result = $query->nextResult()) {
            return (int)$result->get("max_rgt");
        }

        throw new Exception("Unable to fetch MAX(rgt): ".$select->getSQL());
    }

    /**
     * Fetch node data for the given $nodeID and return Node wrapper object
     *
     * @param int $nodeID
     * @return Node
     * @throws Exception
     */
    protected function getNode(int $nodeID) : Node
    {
        $nodeData = $this->getByID($nodeID, "lft", "rgt", "parentID");
        return new Node($nodeID, $nodeData);
    }

    protected function getIDLeft(Node $node): int
    {
        $select = new SQLSelect();
        $select->fields()->set($this->prkey);
        $select->from = $this->table;
        $select->where()->add("lft", $node->rgt() + 1);
        $select->limit = " 1 ";

        $query = new SQLQuery($select);
        $query->exec();

        if ($result = $query->nextResult()) {
            return (int)$result->get($this->prkey);
        }
        return -1;
    }

    protected function getIDRight(Node $node): int
    {
        $select = new SQLSelect();
        $select->fields()->set($this->prkey);
        $select->from = $this->table;
        $select->where()->add("rgt", $node->lft() - 1);
        $select->limit = " 1 ";

        $query = new SQLQuery($select);
        $query->exec();

        if ($result = $query->nextResult()) {
            return (int)$result->get($this->prkey);
        }
        return -1;
    }

    public function moveLeft(int $id, ?DBDriver $db = NULL) : void
    {
        if (!$db) $db = $this->db;

        try {

            $db->transaction();

            $node = $this->getNode($id);

            $brotherID = $this->getIDRight($node);
            if (!$brotherID) throw new Exception("Already at first position");

            $brother = $this->getNode($brotherID);

            $idlist = implode(",", $this->childrenID($node));

            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft - :brotherSize ");
            $update->setExpression("rgt", " rgt - :brotherSize ");
            $update->bind(":brotherSize", $brother->size());

            $update->where()->addExpression(" (lft BETWEEN :nodeLft  AND :nodeRgt) ");
            $update->bind(":nodeLft", $node->lft());
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update);

            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft + :nodeSize ");
            $update->setExpression("rgt", " rgt + :nodeSize ");
            $update->bind(":nodeSize", $node->size());

            $update->where()->addExpression(" (lft BETWEEN :brotherLft AND :brotherRgt )");
            $update->bind(":brotherLft", $brother->lft());
            $update->bind(":brotherRgt", $brother->rgt());
            $update->where()->addExpression("$this->prkey NOT IN (:idlist)");
            $update->bind(":idlist", $idlist);
            $db->query($update);

            $db->commit();

        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    }

    public function insert(array $row, ?DBDriver $db = NULL): int
    {
        $lastid = -1;

        if (!$db) $db = $this->db;

        //defaults to the top level if not specified
        $parentID = 0;
        if (isset($row["parentID"])) {
            //insert using the parentID specified or as top level node
            $parentID = (int)$row["parentID"];
        }
	
        try {
            $db->transaction();

            if ($parentID > 0) {
                $parentNode = $this->getNode($parentID);

                $row["lft"] = $parentNode->rgt();
                $row["rgt"] = $parentNode->rgt() + 1;

                $update = new SQLUpdate($this->select);
                $update->setExpression("rgt", "rgt + 2");
                $update->where()->addExpression(" rgt >= :parentRgt ");
                $update->bind(":parentRgt", $parentNode->rgt());
                $db->query($update);

                $update = new SQLUpdate($this->select);
                $update->setExpression("lft", "lft + 2");
                $update->where()->addExpression(" lft > :parentRgt ");
                $update->bind(":parentRgt", $parentNode->rgt());
                $db->query($update);

                $lastid = parent::insert($row, $db);
            }
            else {
                $lft = $this->getMaxRgt() + 1;
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



    public function update(int $id, array $row, ?DBDriver $db = NULL) : int
    {

        if (!$db) $db = $this->db;

        $oldNode = $this->getNode($id);

        $new_parentID = (int)$row["parentID"];

        if ($new_parentID == $id) throw new Exception("Can not re-parent to self");

        if ($oldNode->parentID() == $new_parentID) return parent::update($id, $row, $db);

        if ($new_parentID > 0 && $this->isChildOf($oldNode, $new_parentID)) {
            throw new Exception("Can not re-parent to child category");
        }

        $lastid = -1;

        try {
            $db->transaction();

            $new_lft = -1;

            if ($new_parentID > 0) {
                $parentNode = $this->getNode($new_parentID);
                $new_lft = $parentNode->rgt();
            }
            else {
                //re-parent to top
                $max_rgt = $this->getMaxRgt();
                $new_lft = $max_rgt + 1;
            }
            //$new_rgt = $new_lft + $oldNode->size() - 1;

            $extent = $oldNode->size();
            $distance = $new_lft - $oldNode->lft();

            $tmppos = $oldNode->lft();

            if ($distance < 0) {
                $distance -= $extent;
                $tmppos += $extent;
            }

            //make space
            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", "lft + :extent");
            $update->where()->addExpression("lft >= :new_lft ");
            $update->bind(":extent", $extent);
            $update->bind(":new_lft", $new_lft);
            $db->query($update);

            $update = new SQLUpdate($this->select);
            $update->setExpression("rgt", "rgt + :extent");
            $update->bind(":extent", $extent);

            $update->where()->addExpression("rgt >= :new_lft");
            $update->bind(":new_lft", $new_lft);
            $db->query($update);

            //move into new space
            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", "lft + :distance");
            $update->setExpression("rgt", "rgt + :distance");
            $update->bind(":distance", $distance);

            $update->where()->addExpression(" (lft >= :temppos AND rgt < :temp_pos + :extent) ");
            $update->bind(":temppos", $tmppos);
            $update->bind(":extent", $extent);
            $db->query($update);

            //remove old space
            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", "lft - :extent");
            $update->bind(":extent", $extent);

            $update->where()->addExpression("lft > :rgt");
            $update->bind(":rgt", $oldNode->rgt());
            $db->query($update);

            $update = new SQLUpdate($this->select);
            $update->setExpression("rgt", "rgt - :extent");
            $update->bind(":extent", $extent);

            $update->where()->addExpression("rgt > :rgt");
            $update->bind(":rgt", $oldNode->rgt());
            $db->query($update);

            $affectedRows = parent::update($id, $row, $db);

            $db->commit();

            return $affectedRows;
        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    }



    public function delete(int $id, ?DBDriver $db = NULL): int
    {

        Debug::ErrorLog("Deleting ID: $id");

        $affectedRows = 0;

        if (!$db) {
            $db = $this->db;
        }

        $node = $this->getNode($id);


        try {

            $db->transaction();

            $affectedRows = parent::delete($id, $db);

            Debug::ErrorLog("Deleted node ID: $id");

            Debug::ErrorLog("Re-parenting child nodes ...");

            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft - 1 ");
            $update->setExpression("rgt", " rgt - 1 ");
            $update->where()->addExpression("(lft BETWEEN :nodeLft AND :nodeRgt)");
            $update->bind(":nodeLft", $node->lft());
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update);

            $update = new SQLUpdate($this->select);
            $update->setExpression("rgt", " rgt - 2 ");
            $update->where()->addExpression("rgt > :nodeRgt");
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update);

            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft - 2 ");
            $update->where()->addExpression(" lft > :nodeRgt ");
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update);

            $update = new SQLUpdate($this->select);
            $update->set("parentID", $node->parentID());
            $update->where()->add("parentID", $id);
            $db->query($update);

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
     * Reconstruct the nested-set tree using parentID relation
     * @param int $lft
     * @param int $cnt
     * @param int $parentID
     * @return void
     * @throws Exception
     */
    public function reconstructNestedSet(int &$lft = -1, int &$cnt = 0, int $parentID = 0) : void
    {

        $select = new SQLSelect();
        $select->fields()->set($this->prkey, "lft", "rgt", "parentID");
        $select->from = $this->table;
        $select->where()->add("parentID", $parentID);
        $select->order_by = " $this->prkey ASC , lft ASC ";

        $query = new SQLQuery($select);
        $query->exec();

        while ($result = $query->nextResult()) {
            $nodeID = (int)$result->get($this->prkey);
            $lft++;

            $this->db->transaction();
            $update = new SQLUpdate();
            $update->from = $this->table;
            $update->set("lft", $lft);
            $update->set("rgt", $cnt);
            $update->where()->add($this->prkey, $nodeID);
            $this->db->query($update);
            $this->db->commit();

            $cnt++;
            $this->reconstructNestedSet($lft, $cnt, $nodeID);

            $this->db->transaction();
            $update = new SQLUpdate();
            $update->from = $this->table;
            $update->set("rgt", $cnt);
            $update->where()->add($this->prkey, $nodeID);
            $this->db->query($update);
            $this->db->commit();
            $lft = $cnt;
            $cnt++;
        }

    }

    /**
     * Return array with IDs of the child nodes
     * @param Node $node
     * @return array
     * @throws Exception
     */
    private function childrenID(Node $node) : array
    {
        $select = $this->nodeSelect($node);
        $select->fields()->set($this->prkey);//prkey only select
        $query = new SQLQuery($select, $this->prkey);
        $query->exec();

        $idlist = array();
        while ($result = $query->nextResult()) {
            $idlist[] = (int)$result->get($this->prkey);
        }
        $query->free();

        return $idlist;
    }

    /**
     * Select all child nodes of the given node
     * ie lft and rgt are between node.lft and node.rgt
     * selected columns are $this->prkey, "lft", "rgt", "parentID"
     * @param Node $node
     * @return SQLSelect
     * @throws Exception
     */
    private function nodeSelect(Node $node) : SQLSelect
    {
        $select = new SQLSelect();
        $select->from = $this->table;
        $select->fields()->set($this->prkey, "lft", "rgt", "parentID");
        $select->where()->addExpression("( lft BETWEEN :nodeLft AND :nodeRgt )");
        $select->bind(":nodeLft", $node->lft());
        $select->bind(":nodeRgt", $node->rgt());
        return $select;
    }

    /**
     * Return true if $id is found as primary key inside the Node children
     * @param Node $node
     * @param int $id
     * @return bool
     * @throws Exception
     */
    private function isChildOf(Node $node, int $id) : bool
    {
        $select = $this->nodeSelect($node);
        //check if $id is found between node lft and node rgt
        $select->where()->add($this->prkey, $id);
        $select->limit = " 1 ";

        $query = new SQLQuery($this->select);

        $query->exec();

        if ($query->nextResult()) return true;

        return false;
    }

    public function moveRight(int $id, ?DBDriver $db = NULL) : void
    {
        if (!$db) $db = $this->db;

        try {

            $db->transaction();

            $node = $this->getNode($id);

            $brotherID = $this->getIDLeft($node);
            if (!$brotherID) throw new Exception("Already in last position");

            $brother = $this->getNode($brotherID);

            //fetch before
            $idlist = implode(" , ", $this->childrenID($node));

            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft + :brotherSize");
            $update->setExpression("rgt", " rgt + :brotherSize");
            $update->bind(":brotherSize", $brother->size());

            $update->where()->addExpression("( lft  BETWEEN :nodeLft AND :nodeRgt )");
            $update->bind(":nodeLft", $node->lft());
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update);

            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft - :nodeSize ");
            $update->setExpression("rgt", " rgt - :nodeSize ");
            $update->bind(":nodeSize", $node->size());

            $update->where()->addExpression("( lft  BETWEEN :brotherLft AND :brotherRgt )");
            $update->bind(":brotherLft", $brother->lft());
            $update->bind(":brotherRgt", $brother->rgt());
            $update->where()->addExpression("$this->prkey NOT IN (:idlist)");
            $update->bind(":idlist", $idlist);
            $db->query($update);

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
        if (strcmp($prefix, "node") !== 0 && strcmp($prefix, "parent") !== 0) {
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

        $sel->where()->addExpression("node.$prkey = $nodeEquals");

       // $sel->group_by = " ";

        //$sel->order_by = " node.lft ";

        return $sel;
    }

    /**
     * Aggregate the tree select counting items from '$relation_table' contained in each branch of the tree
     * '$relation_table' should have column name equal to this bean prkey
     * The count is returned in column named 'related_count'
     * '$relation' is cloned first
     * @param SQLSelect $relation
     * @param string $relation_table
     * @param string $relation_prkey
     * @param array $columns
     * @param bool $with_count
     * @return SQLSelect
     * @throws Exception
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
            $sel->fields()->setAliasExpression("COUNT($relation_table.$relation_prkey)", "related_count");
        }

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
     * @throws Exception
     */
    public function selectChildNodesWith(SQLSelect $other, string $relation_table, int $nodeID = -1, array $columns = array()): SQLSelect
    {
        //other 'from' should be selected as TABLE as relation
        $prefix = "child";

        $fields = array();

        //Ensure primary key is present
        if (!in_array($this->prkey, $columns)) {
            array_unshift($columns, $this->prkey);
        }

        //prepend prefix to column names
        foreach ($columns as $idx => $field) {
            $fields[] = "$prefix.$field";
        }

        $sel = new SQLSelect();
        $sel->fields()->set(...$fields);

        // Using cross join syntax for clarity in nested sets
        $sel->from = " {$this->table} AS node , {$this->table} AS child ";
        // Core Nested Set logic: Find all children within the boundaries of the node
        $sel->where()->addExpression("(child.lft BETWEEN node.lft AND node.rgt)");

        // Join with the relation table
        // Note: Ensure $relation_table is escaped or whitelisted elsewhere
        $sel->where()->addExpression("$relation_table.$this->prkey = child.$this->prkey");

        if ($nodeID > 0) {
            $sel->where()->add("node.{$this->prkey}", $nodeID);
        }
        else {
            // Fallback to root nodes
            $sel->where()->add("node.parentID", 0);
        }

        // Inherit existing filters from current instance
        $this->select->where()->copyTo($sel->where());

        return $sel->combineWith($other);
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
        $sel = $this->selectAggregated($nodeID, $fieldNames);

        $qry = new SQLQuery($sel, $this->prkey, $this->table);
        $qry->exec();

        $ret = array();
        while ($row = $qry->next()) {
            $ret[] = $row;
        }
        return $ret;
    }

}