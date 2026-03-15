<?php
include_once("sql/ISQLGet.php");
include_once("sql/IBindingCollection.php");
include_once("sql/IBindingModifier.php");

include_once("sql/SQLColumnSet.php");
include_once("sql/ClauseCollection.php");


abstract class SQLStatement implements ISQLGet, IBindingCollection, IBindingModifier
{
    protected array $externalBindings = array();

    protected ?SQLColumnSet $fieldset = null;

    protected string $meta = "";
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

    public function __construct(?SQLStatement $other = null)
    {
        $this->fieldset = new SQLColumnSet();
        $this->whereset = new ClauseCollection();

        //copy the where clause collection
        if ($other) {
            $this->from = $other->from;
            $other->where()->copyTo($this->whereset);
            //copy bindings
            $this->externalBindings = $other->externalBindings;
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
     * Create new SQLColumn using ('$name', '$value') and append to the internal fieldset collection.
     * Usage for simple column = value assignments.
     *
     * SQLColumn state after this call:
     *
     * * bindingKey -> ':$name'
     * * value -> '$value'
     * * hasValue -> true
     *
     * $update->set("p.stock_amount", $amount);
     * SQL result p.stock_amount = :p_stock_amount
     * This will bind ":p_stock_amount" -> $amount
     *
     * $update->setExpression("p.stock_amount", "p.stock_amount - 1") - OK
     * OR
     * $update->setExpression("p.stock_amount", "p.stock_amount - :amount"); OK
     * $update->bind(":amount", $amount); OK
     *
     * @param string $name
     * @param array|string|float|int|bool|null $value
     * @return void
     * @throws Exception
     */
    public function set(string $name, array|string|float|int|bool|null $value) : void
    {
        $column = new SQLColumn($name, $value);
        $this->fieldset->setColumn($column);
    }

    /**
     * Configures a column in the statement fieldset to use a raw SQL expression
     * instead of a simple value with automatic name-derived binding.
     *
     * This method transitions the column to "Manual Mode":
     * 1. Disables automatic binding for this specific column.
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
     * @param string $column_name The name of the column/field to target.
     * @param string $expression The raw SQL fragment (e.g., "NOW()", "rgt + :val").
     * @return void
     * @throws Exception If expression validation fails in SQLColumn.
     */
    public function setExpression(string $column_name, string $expression) : void
    {
        // 1. Initialize a new SQLColumn without a value to prevent
        // the automatic generation of a PDO bindingKey.
        $column = new SQLColumn($column_name);

        // 2. Set the raw SQL expression. Passing an empty string for alias
        // ensures the collectSQL method treats this as an UPDATE/SET assignment.
        $column->setExpression($expression);

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
     * Summarize all bindings from fieldset, whereset and any externally added using the bind() method
     * @return array
     * @throws Exception
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

    /**
     * Copy all bindings from $source and set as external bindings to $target
     *
     * @param SQLStatement $target
     * @param SQLStatement $source
     * @return void
     * @throws Exception
     */
    public static function CopyBindings(SQLStatement $target, SQLStatement $source) : void
    {
        Debug::ErrorLog("Copying bindings for SQLStatement");
        SQLStatement::replaceKeyAppend($target->externalBindings, $source->getBindings());
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

    /**
     * Get the accessible meta name for this statement
     * @return string
     */
    public function getMeta() : string
    {
        return $this->meta;
    }

    /**
     * Set the accessible 'meta name' for this statement to '$meta'
     * Used during debug if '$meta' is not empty PDODriver logs the query data
     * @param string $meta
     * @return void
     */
    public function setMeta(string $meta) : void
    {
        $this->meta = $meta;
    }
    public function debugSQL() : string
    {
        return "SQL: ". $this->getSQL()." | Bindings: ".print_r($this->getBindings(), true);
    }
}