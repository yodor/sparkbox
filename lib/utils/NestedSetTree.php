<?php
include_once ("beans/NestedSetBean.php");
include_once ("utils/Node.php");

/**
 * In-memory representation of a nested-set tree.
 * Manages a connected graph of Node objects and offers reconstruction & traversal helpers.
 */
class NestedSetTree
{
    protected NestedSetBean $bean;

    /** @var Node[]  id → Node */
    protected array $nodesById = [];

    /** @var Node[]  root nodes (parentID = 0) */
    protected array $roots = [];

    protected SQLSelect $select;

    public function __construct(NestedSetBean $bean)
    {
        $this->bean = $bean;

        $select = SQLSelect::Table($this->bean->getTableName());
        $select->set($this->bean->key(), "parentID", "lft", "rgt");
        $select->order("lft", OrderDirection::ASC);           // ← critical: pre-order traversal
        $this->select = $select;
    }

    public function setDataColumns(...$columns) : void
    {
        $this->select->set(...$columns);
    }

    /**
     * Loads the tree (or subtree) using the existing lft/rgt values.
     * Nodes are connected via parent-child pointers while preserving nested-set order.
     *
     * @param int $rootId  Optional: load only the subtree rooted at this node
     * @return self
     * @throws Exception
     */
    public function load(int $rootId = 0): self
    {
        $this->nodesById = [];
        $this->roots     = [];

        if ($rootId > 0) {
            $root = $this->bean->getNode($rootId);
            $this->select->where()->expression("lft BETWEEN :nodeLft AND :nodeRgt");
            $this->select->where()->bind(":nodeLft", $root->lft());
            $this->select->where()->bind(":nodeRgt", $root->rgt());
        }

        $query = new SelectQuery($this->select);
        $query->exec();

        $nodesByLft = [];           // temporary: lft → Node (for fast parent lookup)

        while ($row = $query->next()) {
            $id  = (int)$row[$this->bean->key()];
            $node = new Node($id, $row);

            $this->nodesById[$id] = $node;
            $nodesByLft[$node->lft()] = $node;
        }
        $query->free();

        if (empty($this->nodesById)) {
            return $this;
        }

        // Build parent-child pointers using lft/rgt ranges
        // We iterate in pre-order (lft ASC) → parents appear before children
        $stack = [];   // contains open parents

        foreach ($this->nodesById as $node) {
            // Pop parents whose right boundary we have passed
            while (!empty($stack) && $stack[count($stack)-1]->rgt() < $node->lft()) {
                array_pop($stack);
            }

            // If stack is not empty → current node is child of top parent
            if (!empty($stack)) {
                $parent = end($stack);
                $parent->addChild($node);
            } else if ($node->parentID() === 0 || $rootId > 0) {
                // Root level node (or root of requested subtree)
                $this->roots[] = $node;
            }

            // Push current node onto stack (it may have children)
            $stack[] = $node;
        }

        // If we loaded the full tree, roots should match parentID = 0
        // If subtree, roots contains only the requested root
        return $this;
    }

    /**
     * Reconstructs lft/rgt values for the currently loaded tree from the parent-child relations.
     * Writes changes back to the database in a single transaction.
     *
     * @return self
     * @throws Exception
     */
    public function reconstruct(): self
    {
        if (empty($this->nodesById)) {
            throw new Exception("Tree must be loaded before reconstruction");
        }

        $counter = 1;
        $stack   = [];

        // Seed with current roots, reversed to preserve order on pop()
        foreach (array_reverse($this->roots) as $root) {
            $stack[] = ['node' => $root, 'entering' => true];
        }

        while ($stack) {
            $frame = array_pop($stack);
            $node  = $frame['node'];

            if ($frame['entering']) {
                $node->setLft($counter++);
                $stack[] = ['node' => $node, 'entering' => false];

                // Push children in reverse order → pop restores original sequence
                foreach (array_reverse($node->children()) as $child) {
                    $stack[] = ['node' => $child, 'entering' => true];
                }
            } else {
                $node->setRgt($counter++);
            }
        }

        $driver = $this->bean->getDB() ?? DBConnections::Driver();
        try {
            // Bulk write-back in one transaction
            $driver->transaction();

            foreach ($this->nodesById as $node) {
                $upd = SQLUpdate::Table($this->bean->getTableName());
                $upd->set("lft", $node->lft());
                $upd->set("rgt", $node->rgt());
                $upd->where()->match($this->bean->key(), $node->id());
                $driver->query($upd)->free();
            }

            $driver->commit();
        }
        catch (Exception $e) {
            $driver->rollback();
            $message = "Unable to update reconstructed set to the bean table: ".$e->getMessage();
            Debug::ErrorLog($message);
            throw new Exception($message);
        }

        return $this;
    }

    // ────── Basic traversal / query helpers ──────

    /** @return Node[] */
    public function getRoots(): array
    {
        return $this->roots;
    }

    public function getNodeById(int $id): ?Node
    {
        return $this->nodesById[$id] ?? null;
    }

    /** @return Node[] */
    public function getAllNodes(): array
    {
        return array_values($this->nodesById);
    }

    /**
     * Returns the path from root to the given node (including both endpoints)
     *
     * @return Node[]
     */
    public function getPathToRoot(Node $node): array
    {
        $path = [];
        $current = $node;

        while ($current !== null) {
            array_unshift($path, $current);
            $current = $current->parent();
        }

        return $path;
    }

    /**
     * Persist changed lft/rgt of one node back to database
     * (useful after in-memory reordering without full reconstruct)
     */
    public function saveNode(Node $node): bool
    {
        $upd = SQLUpdate::Table($this->bean->getTableName());
        $upd->set("lft", $node->lft());
        $upd->set("rgt", $node->rgt());
        $upd->where()->match($this->bean->key(), $node->id());

        $driver = $this->bean->getDB() ?? DBConnections::Driver();
        $driver->query($upd)->free();

        return true; // or return affected rows if needed
    }
    // Add more helpers as needed: getSubtree(), getLeaves(), getLevel(), etc.

    /**
     * Verifies that loaded graph matches stored parentID values
     * Useful during development or after import
     */
    public function validateParentConsistency(): array
    {
        $mismatches = [];

        foreach ($this->nodesById as $node) {
            $expectedParentId = $node->parentID();
            $actualParentId   = $node->parent() ? $node->parent()->id() : 0;

            if ($expectedParentId !== $actualParentId) {
                $mismatches[$node->id()] = [
                    'expected' => $expectedParentId,
                    'actual'   => $actualParentId
                ];
            }
        }

        return $mismatches;
    }
    /**
     * Returns all nodes in the subtree rooted at the given node (including the root itself).
     *
     * @param Node $root The root of the desired subtree
     * @param bool $includeRoot Whether to include the root node in the result (default: true)
     * @return Node[]
     */
    public function getSubtree(Node $root, bool $includeRoot = true): array
    {
        $result = $includeRoot ? [$root] : [];

        $stack = [$root];
        while ($stack) {
            $current = array_pop($stack);
            foreach ($current->children() as $child) {
                $result[] = $child;
                $stack[] = $child;
            }
        }

        return $result;
    }

    /**
     * Returns all leaf nodes in the currently loaded tree (or in a subtree).
     *
     * @param Node|null $root Optional: restrict to subtree rooted at this node
     * @return Node[]
     */
    public function getLeaves(?Node $root = null): array
    {
        $candidates = $root ? $this->getSubtree($root, true) : $this->getAllNodes();

        $leaves = [];
        foreach ($candidates as $node) {
            if (empty($node->children())) {
                $leaves[] = $node;
            }
        }

        return $leaves;
    }

    /**
     * Returns the depth (level) of a node in the tree.
     * Root nodes have depth 0.
     *
     * @param Node $node The node to measure
     * @return int Depth/level (0 = root)
     */
    public function getLevel(Node $node): int
    {
        $level = 0;
        $current = $node;

        while ($current->parent() !== null) {
            $level++;
            $current = $current->parent();
        }

        return $level;
    }

    /**
     * Returns the number of descendant nodes (excluding the node itself).
     *
     * @param Node $node
     * @return int
     */
    public function getDescendantCount(Node $node): int
    {
        return count($this->getSubtree($node, false));
    }

    /**
     * Returns the total number of nodes in the subtree including the root.
     *
     * @param Node $node
     * @return int
     */
    public function getSubtreeSize(Node $node): int
    {
        return count($this->getSubtree($node, true));
    }

    /**
     * Returns all nodes at a specific level (depth) in the tree.
     *
     * @param int $level Depth to collect (0 = roots)
     * @return Node[]
     */
    public function getNodesAtLevel(int $level): array
    {
        if ($level < 0) {
            return [];
        }

        $result = [];

        foreach ($this->roots as $root) {
            $this->collectNodesAtLevel($root, 0, $level, $result);
        }

        return $result;
    }

    /**
     * Helper for getNodesAtLevel — recursive collector
     */
    private function collectNodesAtLevel(Node $node, int $currentLevel, int $targetLevel, array &$result): void
    {
        if ($currentLevel === $targetLevel) {
            $result[] = $node;
            return;
        }

        if ($currentLevel >= $targetLevel) {
            return;
        }

        foreach ($node->children() as $child) {
            $this->collectNodesAtLevel($child, $currentLevel + 1, $targetLevel, $result);
        }
    }

    /**
     * Returns all nodes in level-order (breadth-first traversal).
     * Useful for layered rendering or processing.
     *
     * @return Node[]
     */
    public function getLevelOrder(): array
    {
        $result = [];
        $queue = $this->roots;

        while ($queue) {
            $node = array_shift($queue);
            $result[] = $node;

            foreach ($node->children() as $child) {
                $queue[] = $child;
            }
        }

        return $result;
    }

    /**
     * Returns basic tree statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $totalNodes = count($this->nodesById);
        $rootCount  = count($this->roots);
        $leafCount  = count($this->getLeaves());
        $maxDepth   = 0;

        foreach ($this->getAllNodes() as $node) {
            $depth = $this->getLevel($node);
            if ($depth > $maxDepth) {
                $maxDepth = $depth;
            }
        }

        return [
            'total_nodes'   => $totalNodes,
            'root_count'    => $rootCount,
            'leaf_count'    => $leafCount,
            'max_depth'     => $maxDepth,
            'average_depth' => $totalNodes > 0 ? round($this->sumOfDepths() / $totalNodes, 2) : 0,
        ];
    }

    /**
     * Internal helper for average depth calculation
     */
    private function sumOfDepths(): int
    {
        $sum = 0;
        foreach ($this->getAllNodes() as $node) {
            $sum += $this->getLevel($node);
        }
        return $sum;
    }
}