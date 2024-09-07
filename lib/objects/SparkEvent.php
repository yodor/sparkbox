<?php
include_once("objects/SparkObject.php");

class SparkEvent extends SparkObject
{
    /**
     * @var int
     */
    protected int $time = 0;

    /**
     * @var SparkObject|null
     */
    protected ?SparkObject $source = null;

    public function __construct(string $name="", SparkObject $source=null)
    {
        parent::__construct();

        $this->setName($name);
        $this->source = $source;
        $this->time = time();
    }

    public function getSource() : ?SparkObject
    {
        return $this->source;
    }

    public function getTime() : int
    {
        return $this->time;
    }

    /**
     * Check if event name is $name
     * @param string $name
     * @return bool
     */
    public function isEvent(string $name) : bool
    {
        return (strcmp($this->name, $name)==0);
    }

}
?>
