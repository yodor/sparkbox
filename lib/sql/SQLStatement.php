<?php
include_once("sql/ClauseCollection.php");
include_once("sql/ISQLGet.php");

abstract class SQLStatement implements ISQLGet, IBindingCollection, IBindingModifier
{
    protected array $externalBindings = array();

    protected ?SQLColumnSet $fieldset = null;

    /**
     * SELECT, UPDATE, DELETE, INSERT
     * @var string
     */
    protected string $type = "";

    /**
     * @var ClauseCollection
     */
    protected ?ClauseCollection $whereset = null;

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
    public abstract function getPreparedSQL() : string;
    public abstract function collectSQL(bool $do_prepared) : string;

    public function __construct(?SQLStatement $other = null)
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
     * Create SQLColumn named $name and set its value to $value
     * No quoting or escaping is done
     * If $name already exists in the fieldset collection it will be replaced
     * @param string $name
     * @param string $value
     * @return void
     */
    public function set(string $name, string $value) : void
    {
        $column = new SQLColumn($name, $value);
        $this->fieldset->setColumn($column);
    }

    /**
     * Return SQLColumn named '$name' from fieldset collection
     * @param string $name
     * @return SQLColumn
     * @throws Exception
     */
    public function get(string $name): SQLColumn
    {
        return $this->fieldset->getColumn($name);
    }

    /**
     * Summarize all bindings from fieldset, whereset and any added using the bind() method
     * @return array
     */
    public function getBindings(): array
    {

        $result = array();
        $this->replaceKeyAppend($result, $this->fieldset->getBindings());
        $this->replaceKeyAppend($result, $this->whereset->getBindings());
        $this->replaceKeyAppend($result, $this->externalBindings);

        return $result;
    }

    private function replaceKeyAppend(array& $target, array $bindings) : void
    {
        foreach ($bindings as $bindingKey => $bindingValue) {
            $target[$bindingKey] = $bindingValue;
        }
    }

    /**
     * Append custom binding to this binding collection
     * @param string $bindingKey
     * @param string|array $value
     * @return void
     */
    public function bind(string $bindingKey, string|array $value) : void
    {
        $this->externalBindings[$bindingKey] = $value;
    }
}