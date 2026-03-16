<?php
/**
 * Represents a node in the nested-set tree with full in-memory graph links.
 * Backward-compatible with the original lightweight Node.
 */
class Node
{
    protected int $parentID = -1;
    protected int $lft = -1;
    protected int $rgt = -1;
    protected int $size = -1;
    protected int $id = -1;
    protected ?array $data = null;

    //graph pointers
    protected ?Node $parent = null;
    protected array $children = [];   // Node[]

    public function __construct(int $id, array $data)
    {
        $this->id       = $id;
        $this->lft      = (int)($data['lft'] ?? 0);
        $this->rgt      = (int)($data['rgt'] ?? 0);
        $this->size     = $this->rgt - $this->lft + 1;
        $this->parentID = (int)($data['parentID'] ?? 0);
        $this->data = $data;
    }

    public function id()       : int   { return $this->id; }
    public function parentID() : int   { return $this->parentID; }
    public function lft()      : int   { return $this->lft; }
    public function rgt()      : int   { return $this->rgt; }
    public function size()     : int   { return $this->size; }

    public function data() : array
    {
        return $this->data;
    }

    //graph accessors
    public function parent(): ?Node
    {
        return $this->parent;
    }

    /** @return Node[] */
    public function children(): array
    {
        return $this->children;
    }

    //graph builders
    public function setParent(?Node $parent): void
    {
        $this->parent = $parent;
        $this->parentID = $parent ? $parent->id() : 0;
    }

    public function addChild(Node $child): void
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    //setters
    public function setLft(int $lft): void
    {
        $this->lft = $lft;
        $this->size = $this->rgt - $this->lft + 1;
    }

    public function setRgt(int $rgt): void
    {
        $this->rgt = $rgt;
        $this->size = $this->rgt - $this->lft + 1;
    }

    // Optional helper (can be used later)
    public function isRoot(): bool
    {
        return $this->parentID === 0 && $this->parent === null;
    }
}