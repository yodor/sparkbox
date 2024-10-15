<?php
include_once("objects/ISparkCollection.php");

class SparkList extends SparkObject implements ISparkCollection
{

    protected array $elements = array();

    public function __construct()
    {
        parent::__construct();
        $this->elements = array();
    }

    public function __clone() : void
    {
        foreach ($this->elements as $idx => $object) {
            $this->elements[$idx] = clone $object;
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

    /**
     * Remove all occurrences of $object from this list
     * @param SparkObject $object
     * @return void
     */
    public function removeAll(SparkObject $object) : void
    {
        $keys = array_keys($this->elements, $object);
        foreach ($keys as $key) {
            unset($this->elements[$key]);
        }
    }

    /**
     * Remove first occurrence of $object from this list
     * @param SparkObject $object
     * @return void
     */
    public function removeObject(SparkObject $object) : void
    {
        $keys = array_keys($this->elements, $object);
        foreach ($keys as $key) {
            unset($this->elements[$key]);
            break;
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


    /**
     * Append all items from '$source' to this list elements
     * @param SparkList $source
     * @param bool $clone Clone each item before appending to this list
     * @return void
     */
    public function appendAll(SparkList $source, bool $clone = false) : void
    {
        $iterator = $source->iterator();
        while ($object = $iterator->next()) {
            if (!($object instanceof SparkObject)) continue;
            if ($clone) {
                $this->append(clone $object);
            }
            else {
                $this->append($object);
            }

        }
    }

}
?>
