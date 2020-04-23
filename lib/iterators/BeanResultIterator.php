<?php
include_once("lib/iterators/SQLIterator.php");

class BeanResultIterator implements SQLIterator
{


    private $bean;
    protected $fields;

    public function __construct(DBTableBean $bean, $fields = " * ")
    {

        $this->bean = $bean;
        $this->fields = $fields;

    }

    public function setFields($fields_str)
    {
        $this->fields = $fields_str;
    }

    public function getBean()
    {
        return $this->bean;
    }

    public function getSelectQuery()
    {
        return $this->bean->selectQuery();
    }

    public function startQuery(SelectQuery $filter = NULL)
    {

        return $this->bean->startSelectIterator($filter);

    }

    public function haveMoreResults(&$row)
    {
        return $this->bean->fetchNext($row);
    }

    public function key()
    {
        return $this->bean->key();
    }

}

?>