<?php
include_once("sql/ClauseCollection.php");

abstract class SQLStatement
{
    /**
     * SELECT, UPDATE, DELETE, INSERT
     * @var string
     */
    protected $type = "";

    /**
     * @var ClauseCollection
     */
    protected $whereset;

    public $from = "";

    public $group_by = "";
    public $order_by = "";
    public $limit = "";
    public $having = "";

    public abstract function getSQL();

    public function __construct()
    {
        $this->whereset = new ClauseCollection();
    }

    public function __clone()
    {
        $this->whereset = clone $this->whereset;
    }

    public function where(): ClauseCollection
    {
        return $this->whereset;
    }

    public function getType() : string
    {
        return $this->type;
    }


}

?>