<?php
include_once("sql/ClauseCollection.php");
include_once("sql/ISQLGet.php");
include_once("sql/IBindingCollection.php");
include_once("sql/IBindingModifier.php");

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
     * @var ClauseCollection|null
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
     * Create new SQLColumn using (name \$name, value \$value) and append to the internal fieldset collection.
     *
     * No quoting or escaping is done.
     *
     * If SQLColumn with name $name already exists in the fieldset collection it will be replaced with this newly created one.
     *
     * By design SQLColumn creates bindingKey using prepending ':' to \$name so this should only be used for safe to be used in prepared statement
     *
     * Example using SQLUpdate:
     *
     * $update->set("p.stock_amount", "p.stock_amount - \$amount");
     *
     * will create bindingKey ':p.stock_amount' with then bound value 'p.stock_amount - \$amount'
     *
     * Correct usage - bind value as statement binding value
     *
     * $update->set("p.stock_amount = p.stock_amount - :amount");
     * $update->bind(":amount", \$amount);
     *
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

    /**
     * Append elements of the input '$bindings' array to the '$target' array.
     *
     * If key is existing in the '$target' array its value will be replaced with value from the '$bindings'.
     *
     * Each value is checked using SQLStatement::IsBoundSafe.
     *
     * @param array $target
     * @param array $bindings
     * @return void
     * @throws Exception throws exception if value is not bound safe
     */
    private static function replaceKeyAppend(array& $target, array $bindings) : void
    {
        foreach ($bindings as $bindingKey => $bindingValue) {
            if (SQLStatement::IsBoundSafe($bindingValue)) {
                $target[$bindingKey] = $bindingValue;
            }
            else throw new Exception("[$bindingKey] is not SQLStatement::IsBoundSafe");
        }
    }

    public function bind(string $bindingKey, array|string|int|float|bool|null $value) : void
    {
        if (!$bindingKey) throw new Exception("bindingKey is empty");
        $this->externalBindings[$bindingKey] = $value;
    }

    /**
     * Format input to be used as a bindingKey by prepending it with ":"
     * @param string $name
     * @return string
     */
    public static function FormatBindingKey(string $name) : string
    {
        return ":".$name;
    }

    /**
     * Check if value is safe to be used as value during named parameter binding.
     * True if (is_scalar($value) || is_null($value) || is_array($value))
     * @param mixed $value
     * @return bool True if value is scalar, array or null
     */
    public static function IsBoundSafe(mixed $value) : bool
    {
        if ( is_scalar($value) || is_array($value) || is_null($value) ) return true;
        return false;
    }
}