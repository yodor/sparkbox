<?php
class SparkObject implements jsonSerializable
{
    /**
     * @var SparkObject|null
     */
    protected ?SparkObject $parent = null;

    /**
     * @var string
     */
    protected string $name = "";

    public function __construct()
    {

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
     * Return hash of the object
     * Hash value is constructed using json_encoded result of '$this'
     * Uses the default sparkHash for actual hashing
     * @return string Currently uses the sparkHash function that use xxh3 algorithm
     */
    public function hash(): string
    {
        return Spark::Hash(json_encode($this));
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

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }

    public function __serialize(): array
    {
        return get_object_vars($this);
    }
}
?>
