<?php
include_once("beans/DBTableBean.php");
include_once("sql/SQLSelect.php");
include_once("sql/SQLUpdate.php");
include_once("utils/Node.php");

/**
 * NestedSetBean implements the classic nested-set (lft/rgt) model for hierarchical data.
 *
 * Extends DBTableBean and requires the columns `lft`, `rgt`, and `parentID`.
 */
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

    /**
     * Constructs the bean and validates required nested-set columns.
     *
     * @param string $table_name Table name.
     * @param DBDriver|null $dbdriver Database driver (optional).
     * @throws Exception If `lft`, `rgt`, or `parentID` columns are missing.
     */
    public function __construct(string $table_name, ?DBDriver $dbdriver = NULL)
    {
        parent::__construct($table_name, $dbdriver);
        if (!$this->haveColumn("lft") || !$this->haveColumn("rgt") || !$this->haveColumn("parentID")) {
            throw new Exception("Incorrect table columns for NestedSetBean");
        }
    }

    /**
     * Returns the highest rgt value currently stored in the table.
     *
     * @return int
     * @throws Exception If the query fails.
     */
    private function getMaxRgt(): int
    {
        //clean select not the bean one that might have filtering applied
        $select = new SQLSelect();
        $select->from = $this->table;
        $select->setAliasExpression(" MAX(rgt) ", "max_rgt");
        $select->limit = " 1 ";

        $query = new SelectQuery($select);
        $query->exec();

        if ($result = $query->nextResult()) {
            $query->free();
            return (int)$result->get("max_rgt");
        }

        throw new Exception("Unable to fetch MAX(rgt): ".$select->getSQL());
    }

    /**
     * Fetches node data and wraps it in a Node object.
     *
     * @param int $nodeID Primary key.
     * @return Node
     * @throws Exception
     */
    public function getNode(int $nodeID) : Node
    {
        $nodeData = $this->getByID($nodeID, "lft", "rgt", "parentID");
        return new Node($nodeID, $nodeData);
    }

    /**
     * Returns an array of all descendant node IDs (including the node itself).
     *
     * @param Node $node
     * @return array
     * @throws Exception
     */
    private function childrenID(Node $node) : array
    {
        $select = $this->nodeSelect($node);
        $select->set($this->prkey);//prkey only select
        $query = new SelectQuery($select, $this->prkey);
        $query->exec();

        $idlist = array();
        while ($result = $query->nextResult()) {
            $idlist[] = (int)$result->get($this->prkey);
        }
        $query->free();

        return $idlist;
    }

    /**
     * Builds a SELECT that returns all descendants of the given node.
     *
     * Selected columns: prkey, lft, rgt, parentID.
     *
     * @param Node $node
     * @return SQLSelect
     * @throws Exception
     */
    private function nodeSelect(Node $node) : SQLSelect
    {
        $select = new SQLSelect();
        $select->from = $this->table;
        $select->set($this->prkey, "lft", "rgt", "parentID");
        $select->where()->addExpression("( lft BETWEEN :nodeLft AND :nodeRgt )");
        $select->bind(":nodeLft", $node->lft());
        $select->bind(":nodeRgt", $node->rgt());
        return $select;
    }

    /**
     * Checks whether the given ID belongs to the subtree of the node.
     *
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

        $query = new SelectQuery($select);
        $query->exec();
        if ($query->nextResult()) {
            $query->free();
            return true;
        }
        return false;
    }

    /**
     * Returns the ID of the next sibling (node immediately to the right).
     *
     * @return int ID of next sibling or -1 if none exists
     * @throws Exception
     */
    private function getNextSiblingID(Node $node): int
    {
        $select = new SQLSelect();
        $select->set($this->prkey);
        $select->from = $this->table;
        $select->where()->add("lft", $node->rgt() + 1);
        $select->limit = " 1 ";

        $query = new SelectQuery($select);
        $query->exec();

        if ($result = $query->nextResult()) {
            $query->free();
            return (int)$result->get($this->prkey);
        }
        return -1;
    }

    /**
     * Returns the ID of the previous sibling (node immediately to the left).
     *
     * @return int ID of previous sibling or -1 if none exists
     * @throws Exception
     */
    private function getPreviousSiblingID(Node $node): int
    {
        $select = new SQLSelect();
        $select->set($this->prkey);
        $select->from = $this->table;
        $select->where()->add("rgt", $node->lft() - 1);
        $select->limit = " 1 ";

        $query = new SelectQuery($select);
        $query->exec();

        if ($result = $query->nextResult()) {
            $query->free();
            return (int)$result->get($this->prkey);
        }
        return -1;
    }

    /**
     * Moves the specified node one position to the left among its siblings
     * (swaps position with the previous sibling).
     *
     * @param int $id The primary key of the node to move
     * @param DBDriver|null $db Optional database driver to use within transaction
     * @return void
     * @throws Exception When the node is already the leftmost sibling
     */
    public function moveLeft(int $id, ?DBDriver $db = NULL) : void
    {

        $node = $this->getNode($id);

        $brotherID = $this->getPreviousSiblingId($node);
        if ($brotherID === -1) throw new Exception("Node is already at the first (leftmost) position among siblings");

        $brother = $this->getNode($brotherID);

        $children = $this->childrenID($node);

        $code = function (DBDriver $db) use ($node, $brother, $children)
        {
            // Shift the target node and its subtree LEFT by the size of the previous sibling
            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft - :brotherSize ");
            $update->setExpression("rgt", " rgt - :brotherSize ");
            $update->bind(":brotherSize", $brother->size());

            $update->where()->addExpression(" (lft BETWEEN :nodeLft  AND :nodeRgt) ");
            $update->bind(":nodeLft", $node->lft());
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update)->free();

            // Shift the previous sibling and everything between them RIGHT by the size of the target node
            // (excluding the target subtree itself)
            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft + :nodeSize ");
            $update->setExpression("rgt", " rgt + :nodeSize ");
            $update->bind(":nodeSize", $node->size());

            $update->where()->addExpression(" (lft BETWEEN :brotherLft AND :brotherRgt )");
            $update->bind(":brotherLft", $brother->lft());
            $update->bind(":brotherRgt", $brother->rgt());
            //bind ids and get the placeholders comma separated
            $idlist = $update->bindList($children);
            $update->where()->addExpression("$this->prkey NOT IN ($idlist)");

            $db->query($update)->free();

        };

        $this->handleTransaction($code, $db);

    }

    /**
     * Moves the specified node one position to the right among its siblings
     * (swaps position with the next sibling).
     *
     * @param int $id The primary key of the node to move
     * @param DBDriver|null $db Optional database driver to use within transaction
     * @return void
     * @throws Exception When the node is already the rightmost sibling
     */
    public function moveRight(int $id, ?DBDriver $db = NULL) : void
    {

        $node = $this->getNode($id);

        $brotherID = $this->getNextSiblingID($node);
        if ($brotherID === -1) throw new Exception("Node is already at the last (rightmost) position among siblings");

        $brother = $this->getNode($brotherID);

        //fetch before
        $children = $this->childrenID($node);

        $code = function(DBDriver $db) use ($node, $brother, $children) {

            // Shift the target node and its subtree RIGHT by the size of the next sibling
            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft + :brotherSize");
            $update->setExpression("rgt", " rgt + :brotherSize");
            $update->bind(":brotherSize", $brother->size());

            $update->where()->addExpression("( lft  BETWEEN :nodeLft AND :nodeRgt )");
            $update->bind(":nodeLft", $node->lft());
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update)->free();

            // Shift the next sibling and everything between them LEFT by the size of the target node
            // (excluding the target subtree itself)
            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft - :nodeSize ");
            $update->setExpression("rgt", " rgt - :nodeSize ");
            $update->bind(":nodeSize", $node->size());

            $update->where()->addExpression("( lft  BETWEEN :brotherLft AND :brotherRgt )");
            $update->bind(":brotherLft", $brother->lft());
            $update->bind(":brotherRgt", $brother->rgt());

            $idlist = $update->bindList($children);
            $update->where()->addExpression("$this->prkey NOT IN ($idlist)");


            $db->query($update)->free();
        };

        $this->handleTransaction($code, $db);

    }

    /**
     * Inserts a new node, automatically calculating lft/rgt.
     *
     * If parentID is omitted or 0, the node becomes a root.
     *
     * @param array $row Data row (parentID optional).
     * @param DBDriver|null $db Optional driver.
     * @return int New primary key.
     * @throws Exception
     */
    public function insert(array $row, ?DBDriver $db = NULL): int
    {
        //defaults to the top level if not specified
        $parentID = 0;
        if (isset($row["parentID"])) {
            //insert using the parentID specified or as top level node
            $parentID = (int)$row["parentID"];
        }

        if ($parentID == 0) {
            $lft = $this->getMaxRgt() + 1;
            $row["lft"] = $lft;
            $row["rgt"] = $lft + 1;
            return parent::insert($row, $db);
        }

        $lastID = -1;

        $code = function (DBDriver $db) use ($row, $parentID, &$lastID) {

            $parentNode = $this->getNode($parentID);

            $row["lft"] = $parentNode->rgt();
            $row["rgt"] = $parentNode->rgt() + 1;

            $update = new SQLUpdate($this->select);
            $update->setExpression("rgt", "rgt + 2");
            $update->where()->addExpression(" rgt >= :parentRgt ");
            $update->bind(":parentRgt", $parentNode->rgt());
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", "lft + 2");
            $update->where()->addExpression(" lft > :parentRgt ");
            $update->bind(":parentRgt", $parentNode->rgt());
            $db->query($update)->free();

            $lastID = parent::insert($row, $db);

        };
        $this->handleTransaction($code, $db);

        return $lastID;
    }

    /**
     * Updates a node, including optional re-parenting with full lft/rgt adjustment.
     *
     * Prevents self-parenting and descendant-to-ancestor moves.
     *
     * @param int $id Node ID.
     * @param array $row New data (parentID triggers re-parenting).
     * @param DBDriver|null $db Optional driver.
     * @return int Affected rows.
     * @throws Exception On invalid re-parenting.
     */
    public function update(int $id, array $row, ?DBDriver $db = NULL) : int
    {

        $node = $this->getNode($id);
        $new_parentID = (int)$row["parentID"];

        if ($new_parentID == $id) throw new Exception("Can not re-parent to self");

        if ($node->parentID() == $new_parentID) return parent::update($id, $row, $db);

        if ($new_parentID > 0 && $this->isChildOf($node, $new_parentID)) {
            throw new Exception("Can not re-parent to child category");
        }

        $code = function(DBDriver $db) use($row, $new_parentID, $node)
        {
            $new_lft = -1;

            if ($new_parentID > 0) {
                $parentNode = $this->getNode($new_parentID);
                $new_lft = $parentNode->rgt();
            } else {
                //re-parent to top
                $max_rgt = $this->getMaxRgt();
                $new_lft = $max_rgt + 1;
            }
            //$new_rgt = $new_lft + $oldNode->size() - 1;

            $extent = $node->size();
            $distance = $new_lft - $node->lft();

            $tmppos = $node->lft();

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
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->setExpression("rgt", "rgt + :extent");
            $update->bind(":extent", $extent);

            $update->where()->addExpression("rgt >= :new_lft");
            $update->bind(":new_lft", $new_lft);
            $db->query($update)->free();

            //move into new space
            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", "lft + :distance");
            $update->setExpression("rgt", "rgt + :distance");
            $update->bind(":distance", $distance);

            $update->where()->addExpression(" (lft >= :temppos AND rgt < :temp_pos + :extent) ");
            $update->bind(":temppos", $tmppos);
            $update->bind(":extent", $extent);
            $db->query($update)->free();

            //remove old space
            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", "lft - :extent");
            $update->bind(":extent", $extent);

            $update->where()->addExpression("lft > :rgt");
            $update->bind(":rgt", $node->rgt());
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->setExpression("rgt", "rgt - :extent");
            $update->bind(":extent", $extent);

            $update->where()->addExpression("rgt > :rgt");
            $update->bind(":rgt", $node->rgt());
            $db->query($update)->free();

            parent::update($node->id(), $row, $db);

        };

        return $this->handleTransaction($code, $db);

    }

    /**
     * Deletes the node and re-parents its children to its former parent.
     *
     * Adjusts all lft/rgt values to close the gap.
     *
     * @param int $id Node ID.
     * @param DBDriver|null $db Optional driver.
     * @return int Affected rows.
     * @throws Exception
     */
    public function delete(int $id, ?DBDriver $db = NULL): int
    {

        $node = $this->getNode($id);

        $code = function(DBDriver $db) use ($node) {

            Debug::ErrorLog("Deleting ID: ".$node->id());
            parent::delete($node->id(), $db);

            Debug::ErrorLog("Re-parenting child nodes ...");

            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft - 1 ");
            $update->setExpression("rgt", " rgt - 1 ");
            $update->where()->addExpression("(lft BETWEEN :nodeLft AND :nodeRgt)");
            $update->bind(":nodeLft", $node->lft());
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->setExpression("rgt", " rgt - 2 ");
            $update->where()->addExpression("rgt > :nodeRgt");
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->setExpression("lft", " lft - 2 ");
            $update->where()->addExpression(" lft > :nodeRgt ");
            $update->bind(":nodeRgt", $node->rgt());
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->set("parentID", $node->parentID());
            $update->where()->add("parentID", $node->id());
            $db->query($update)->free();
        };

        return $this->handleTransaction($code, $db);

    }

    /**
     * Rebuilds lft/rgt values for the entire tree from parentID.
     *
     * - Single SELECT
     * - Builds full in-memory graph of Node objects (pointers)
     * - Single iterative traversal
     * - Single transaction write-back
     * - Fully compatible with unbuffered PDO
     *
     * @return void
     * @throws Exception
     */
    public function reconstructNestedSet(): void
    {
        // Phase 1: Bulk load → create Node objects
        $select = new SQLSelect();
        $select->set($this->prkey, "parentID");
        $select->from = $this->table;
        $select->order_by = "$this->prkey ASC";

        $query = new SelectQuery($select);
        $query->exec();

        $allNodes = [];     // id → Node
        $childrenMap = [];  // parentID → [childId, ...] (preserves order)

        while ($row = $query->next()) {
            $id  = (int)$row[$this->prkey];
            $pid = (int)$row["parentID"];

            $allNodes[$id] = new Node($id, [
                'lft'      => 0,
                'rgt'      => 0,
                'parentID' => $pid
            ]);

            $childrenMap[$pid] = $childrenMap[$pid] ?? [];
            $childrenMap[$pid][] = $id;
        }
        $query->free();

        if (empty($allNodes)) {
            return;
        }

        // Phase 2: Build the full graph using pointers
        foreach ($allNodes as $node) {
            $pid = $node->parentID();
            if ($pid !== 0 && isset($allNodes[$pid])) {
                $allNodes[$pid]->addChild($node);   // sets both parent and child links
            }
        }

        // Phase 3: Iterative DFS to assign lft/rgt (using the live graph)
        $counter = 1;
        $stack = [];

        // Seed roots (parentID = 0)
        $roots = $childrenMap[0] ?? [];
        foreach (array_reverse($roots) as $id) {
            $stack[] = ['node' => $allNodes[$id], 'entering' => true];
        }

        while ($stack) {
            $frame = array_pop($stack);
            $node  = $frame['node'];

            if ($frame['entering']) {
                $node->setLft($counter++);
                $stack[] = ['node' => $node, 'entering' => false];

                // Push children in reverse (so pop() restores original order)
                foreach (array_reverse($node->children()) as $child) {
                    $stack[] = ['node' => $child, 'entering' => true];
                }
            } else {
                $node->setRgt($counter++);
            }
        }

        // Phase 4: Single transaction write-back
        $this->db->transaction();

        foreach ($allNodes as $node) {
            $upd = new SQLUpdate();
            $upd->from = $this->table;
            $upd->set("lft", $node->lft());
            $upd->set("rgt", $node->rgt());
            $upd->where()->add($this->prkey, $node->id());
            $this->db->query($upd);
        }

        $this->db->commit();
    }

    /**
     * Returns a SELECT suitable for tree listing (self-join on node/parent).
     *
     * Use prefix 'node' for simple lists or 'parent' for aggregated lists.
     *
     * When doing simple tree list use $prefix='node'
     *
     * When doing aggregate tree list use $prefix='parent'
     *
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

        $sel->set(...$fields);

        $sel->from = " $this->table AS node, $this->table AS parent ";
        $sel->where()->addExpression("( node.lft BETWEEN parent.lft AND parent.rgt )");

        $this->select->where()->copyTo($sel->where());

        $sel->group_by = " $prefix." . $this->prkey;
        $sel->order_by = " $prefix.lft ";

        return $sel;
    }

    /**
     * Returns aggregated tree data for a specific node.
     *
     * @param string $nodeEquals Expression or value for the target node.
     * @param array $fieldNames Additional fields.
     * @return SQLSelect
     * @throws Exception
     */
    public function selectAggregated(string $nodeEquals, array $fieldNames = array()): SQLSelect
    {
        $prkey = $this->prkey;

        $sel = $this->selectTree($fieldNames, "parent");

        $sel->where()->addExpression("node.$prkey = $nodeEquals");

        return $sel;
    }

    /**
     * Joins the tree with a related table and optionally counts related rows per branch.
     *
     * Aggregate the tree select counting items from '$relation_table' contained
     * in each branch of the tree.
     * * '$relation_table' should have column name equal to this bean prkey
     * * The count is returned in column named 'related_count'
     * * '$relation' is cloned first
     * @param SQLSelect $relation Base query on the relation table.
     * @param string $relation_table Related table name.
     * @param string $relation_prkey Its primary-key column.
     * @param array $columns Extra columns.
     * @param bool $with_count Include related_count column.
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
        $result->reset();

        $prkey = $this->prkey;

        $sel = $this->selectAggregated("$relation_table.$prkey", $columns);

        if ($with_count) {
            $sel->setAliasExpression("COUNT($relation_table.$relation_prkey)", "related_count");
        }

        return $sel->combineWith($result);

    }

    /**
     * Selects a node and all its descendants joined with another table for aggregation.
     *
     * @param SQLSelect $other Query on the related table.
     * @param string $relation_table Related table.
     * @param int $nodeID Node to start from (0 = roots).
     * @param array $columns Columns to return.
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
        $sel->set(...$fields);

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
     * Returns the chain of ancestor nodes for the given node.
     *
     * @param int $nodeID Starting node.
     * @param array $fieldNames Extra fields to return.
     * @return array
     * @throws Exception
     */
    public function getParentNodes(int $nodeID, array $fieldNames = array()): array
    {
        $sel = $this->selectAggregated($nodeID, $fieldNames);

        $qry = new SelectQuery($sel, $this->prkey, $this->table);
        $qry->exec();

        $ret = array();
        while ($row = $qry->next()) {
            $ret[] = $row;
        }
        $qry->free();
        return $ret;

    }

}