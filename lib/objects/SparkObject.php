<?php
class SparkObject
{
    /**
     * @var SparkObject|null
     */
    protected ?SparkObject $parent;

    /**
     * @var string
     */
    protected string $name = "";

    public function __construct()
    {
        $this->name = "";
        $this->parent = null;
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
     * Return hash of the serialized value of this object
     * @return string Currently uses the sparkHash function that use xxh3 algorithm
     */
    public function hash(): string
    {
        return sparkHash(serialize($this));
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
