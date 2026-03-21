<?php

class SparkObject
{
    /**
     * @var SparkObject|null
     */
    protected ?SparkObject $parent = null;

    /**
     * @var string
     */
    protected string $name = "";

    public function __construct(?SparkObject $parent = null)
    {
        $this->parent = $parent;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function setParent(?SparkObject $parent) : void
    {
        $this->parent = $parent;
    }

    /**
     * @return SparkObject|null
     */
    public function getParent(): ?SparkObject
    {
        return $this->parent;
    }

    /**
     * Return simple hash of the object.
     *
     * Hash value is constructed using get_object_vars and ksort
     *
     * Final value is hashed using Spark::Hash
     * Fast + reasonably safe (scalar-only objects, stable order not required)
     * @return string Currently uses the sparkHash function that use xxh3 algorithm
     */
    public function hash(): string
    {
        $vars = get_object_vars($this);
        ksort($vars); // crucial for stability

        $parts = [];
        foreach ($vars as $k => $v) {
            $parts[] = "\x1F".$k . "\x1F" . (string)$v; // \x1F = unit separator (rarely appears in data)
        }

        return Spark::Hash(implode("\x1E", $parts));    // \x1E = record separator
    }

    /**
     * Compare objects using the hash() method result
     * @param SparkObject $other
     * @return bool True if this object.hash() result is equal to '$other' object hash() result
     */
    public function equals( SparkObject $other ) : bool
    {
        return (strcmp($this->hash(), $other->hash())===0);
    }

}