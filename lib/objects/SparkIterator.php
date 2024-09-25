<?php
include_once ("objects/ISparkIterator.php");
include_once ("objects/ISparkCollection.php");

class SparkIterator implements ISparkIterator
{

    protected int $pmax = -1;
    protected int $pos = -1;

    protected ISparkCollection $collection;
    protected array $keys = array();

    public function __construct(ISparkCollection $collection)
    {
        $this->collection = $collection;
        $this->keys = $this->collection->keys();
        $this->pmax = count($this->keys)-1;
        $this->pos = -1;
    }

    public function reset() : void
    {
        $this->pos = -1;
    }

    public function next() : ?SparkObject
    {
        if ($this->pos<$this->pmax) {
            $this->pos++;
            return $this->collection->get($this->keys[$this->pos]);
        }
        else return null;
    }

    public function key() : int|string|null
    {
        if (isset($this->keys[$this->pos])) {
            return $this->keys[$this->pos];
        }
        return null;
    }

    public function pos() : int
    {
        return $this->pos;
    }
}
?>
