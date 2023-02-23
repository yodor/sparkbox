<?php
class SparkObject {
    /**
     * @var SparkObject
     */
    protected $parent = null;

    /**
     * @var string
     */
    protected $name = "";

    public function __construct()
    {
        $this->name = "";
    }

    public function getName() : string
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


}
?>