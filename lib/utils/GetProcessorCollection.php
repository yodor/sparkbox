<?php
include_once("utils/GETProcessor.php");
class GetProcessorCollection {

    protected array $contents;
    public function __construct()
    {
        $this->contents = array();
    }
    public function append(GETProcessor $filter) {
        $this->contents[$filter->getName()] = $filter;
    }
    public function get(string $name) : GETProcessor
    {
        return $this->contents[$name];
    }
    public function have(string $name) : bool
    {
        return isset($this->contents[$name]);
    }
    public function clear()
    {
        $this->contents = array();
    }
    public function count()
    {
        return count($this->contents);
    }
    public function getAll() : array
    {
        return $this->contents;
    }

    public function iterator() : ArrayIterator
    {
        return new ArrayIterator($this->contents);
    }
}
?>
