<?php
include_once("lib/iterators/SQLQuery.php");

class BeanQuery extends SQLQuery
{

    protected $bean;

    public function __construct(DBTableBean $bean)
    {
        parent::__construct($bean->select(), $bean->key(), $bean->getDB());
        $this->bean = $bean;
    }

    public function getBean() : DBTableBean
    {
        return $this->bean;
    }

}

?>