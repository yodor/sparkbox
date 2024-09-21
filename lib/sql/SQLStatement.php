<?php
include_once("sql/ClauseCollection.php");
include_once("sql/ISQLGet.php");

abstract class SQLStatement implements ISQLGet
{
    protected SQLColumnSet $fieldset;

    /**
     * Set of column names and values to operate the statement with during insert or update
     * @var array
     */
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
     * Table name for the statement
     * Also for insert
     * @var string Table name
     */
    public string $from = "";

    public string $group_by = "";
    public string $order_by = "";
    public string $limit = "";
    public string $having = "";

    /**
     * Returns SQL text for this statement
     * @return string
     * @throws Exception
     */
    public abstract function getSQL() : string;

    public function __construct(SQLStatement $other = null)
    {
        $this->fieldset = new SQLColumnSet();
        $this->whereset = new ClauseCollection();

        //copy the where clause collection
        if ($other) {
            $this->from = $other->from;
            $other->where()->copyTo($this->whereset);
        }
    }

    public function __clone() : void
    {
        $this->whereset = clone $this->whereset;
        $this->fieldset = clone $this->fieldset;
    }

    public function fields(): SQLColumnSet
    {
        return $this->fieldset;
    }

    public function where(): ClauseCollection
    {
        return $this->whereset;
    }

    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Set single value for column '$column'
     * No quoting or escaping is done
     * @param string $column
     * @param string $value
     * @return void
     */
    public function set(string $column, string $value) : void
    {
        $this->set[$column] = $value;
    }

}

?>
