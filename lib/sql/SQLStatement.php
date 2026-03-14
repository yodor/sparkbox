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
     * Configures a column in the statement fieldset to use a raw SQL expression
     * instead of a simple value with automatic name-derived binding.
     *
     * This method transitions the column to "Manual Mode":
     * 1. Disables automatic PDO binding for this specific column.
     * ($column->getBindingKey() returns an empty string)
     * 2. Allows for database-side calculations (arithmetic, functions, subqueries).
     * 3. Supports manual parameter binding for high-security custom logic.
     *
     * * Basic usage with SQL functions:
     *
     * $stmt->setExpression("update_date", "NOW()");
     * $stmt->setExpression("rgt", "rgt + 2");
     *
     * * Advanced usage with manual binding (Prepared Statement):
     *
     * $stmt->setExpression("rgt", "rgt + :value");
     * $stmt->bind(":value", 2);
     *
     * @param string $name The name of the column/field to target.
     * @param string $expression The raw SQL fragment (e.g., "NOW()", "rgt + :val").
     * @return void
     * @throws Exception If expression validation fails in SQLColumn.
     */
    public function setExpression(string $name, string $expression) : void
    {
        // 1. Initialize a new SQLColumn without a value to prevent
        // the automatic generation of a PDO bindingKey.
        $column = new SQLColumn($name, "");

        // 2. Set the raw SQL expression. Passing an empty string for alias
        // ensures the collectSQL method treats this as an UPDATE/SET assignment.
        $column->setExpression($expression, "");

        // 3. Register the configured column object into the statement's fieldset
        // so it can be included during the SQL generation process.
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
    protected static function replaceKeyAppend(array& $target, array $bindings) : void
    {
//        Debug::ErrorLog("Appending Bindings: ", $bindings);
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
     * and ensuring it contains only valid characters for PDO.
     * * @param string $name
     * @return string
     */
    public static function FormatBindingKey(string $name) : string
    {

        $name = trim($name);

        //Cover cases like "node.catID", "user-name" или "table alias.field"
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);

        //No starting digit (PDO requirement) - append "p_"
        if (preg_match('/^[0-9]/', $safeName)) {
            $safeName = "p_" . $safeName;
        }

        return ":" . $safeName;
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