<?php
include_once("sql/ClauseCollection.php");

abstract class SQLStatement
{
    protected array $set = array();
    /**
     * SELECT, UPDATE, DELETE, INSERT
     * @var string
     */
    protected string $type = "";

    /**
     * @var ClauseCollection
     */
    protected ClauseCollection $whereset;

    /**
     * @var string Table name
     */
    public string $from = "";

    public string $group_by = "";
    public string $order_by = "";
    public string $limit = "";
    public string $having = "";

    public abstract function getSQL() : string;

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

    public function set(string $column, string $value) : void
    {
        $this->set[$column] = $value;
    }
}

?>
