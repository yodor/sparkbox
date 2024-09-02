<?php
include_once ("objects/ISparkCollection.php");

class SparkMap implements ISparkCollection
{

    protected array $elements = array();

    public function __construct()
    {
        $this->elements = array();
    }

    public function __clone() : void
    {
        foreach ($this->elements as $idx => $clause) {
            $this->elements[$idx] = clone $clause;
        }
    }

    public function count() : int
    {
        return count($this->elements);
    }

    public function keys() : array
    {
        return array_keys($this->elements);
    }

    public function get(string|int $key) : ?SparkObject
    {
        if (isset($this->elements[$key])) {
            return $this->elements[$key];
        }
        return null;
    }

    public function clear(): void
    {
        $this->elements = array();
    }

    public function add(string $key, SparkObject $object) : void
    {
        $this->elements[$key] = $object;
    }

    public function remove(string $key) : void
    {
        if (isset($this->elements[$key])) {
            unset($this->elements[$key]);
        }
    }

    public function toArray() : array
    {
        return $this->elements;
    }


    public function iterator(): SparkIterator
    {
        return new SparkIterator($this);
    }

    /**
     * @param SparkObject $object
     * @param bool $strict
     * @return bool
     */
    public function contains(SparkObject $object, bool $strict = false): bool
    {
        return in_array($object, $this->elements, $strict);
    }
}
?>
