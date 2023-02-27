<?php
class SparkObject
{
    /**
     * @var SparkObject
     */
    protected $parent = NULL;

    /**
     * @var string
     */
    protected $name = "";

    public function __construct()
    {
        $this->name = "";
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setParent(?SparkObject $parent)
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
     * Return hash of the serialized value of this object
     * @return string Currently uses the crc32c algorithm
     */
    public function hash(): string
    {
        return crc32(serialize($this));
    }

    /**
     * Compare objects using the hash() method result
     * @param SparkObject $other
     * @return bool True if this object.hash() result is equal to '$other' object hash() result
     */
    public function equals( SparkObject $other ) : bool
    {
        return (strcmp($this->hash(), $other->hash())==0);
    }

}
?>