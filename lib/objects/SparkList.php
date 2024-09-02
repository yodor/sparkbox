<?php
include_once("objects/ISparkCollection.php");

class SparkList implements ISparkCollection
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

    public function keys(): array
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

    public function count() : int
    {
        return count($this->elements);
    }

    public function clear(): void
    {
        $this->elements = array();
    }

    public function append(SparkObject $object) : void
    {
        $this->elements[] = $object;
    }

    public function insert(SparkObject $object, int $index) : void
    {
        array_splice($this->elements, $index, 0, array($object));
    }

    public function prepend(SparkObject $object) : void
    {
        $this->insert($object, 0);
    }

    public function remove(int $index) : void
    {
        if (isset($this->elements[$index])) {
            unset($this->elements[$index]);
        }
    }

    public function removeAll(SparkObject $object) : void
    {
        $keys = array_keys($this->elements, $object);
        foreach ($keys as $key) {
            unset($this->elements[$key]);
        }
    }

    public function indexOf(SparkObject $object, int $from=0) : int
    {
        $keys = array_keys($this->elements, $object);

        $value = array_search($from, $keys);
        if ($value === false) return -1;
        return $value;
    }

    /**
     * @param SparkObject $object
     * @param bool $strict
     * @return bool
     */
    public function contains(SparkObject $object, bool $strict = false) : bool
    {
        return in_array($object, $this->elements, $strict);
    }

    public function toArray() : array
    {
        return $this->elements;
    }

    public function iterator(): SparkIterator
    {
        return new SparkIterator($this);
    }


}
?>
