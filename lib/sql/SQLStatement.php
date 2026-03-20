<?php
include_once("sql/ISQLGet.php");
include_once("sql/IBindingCollection.php");
include_once("sql/IBindingModifier.php");

include_once("sql/SQLColumnSet.php");
include_once("sql/ClauseCollection.php");
include_once("sql/FromExpression.php");
include_once("sql/LimitExpression.php");

abstract class SQLStatement implements ISQLGet, IBindingCollection, IBindingModifier
{
    protected ?LimitExpression $_limit = null;

    protected ?FromExpression $_from = null;

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

    public string $group_by = "";

    public string $having = "";

    public abstract function getSQL() : string;

    /**
     * Copy whereset and external bindings only
     * @param SQLStatement|null $other
     */
    public function __construct(?SQLStatement $other = null)
    {
        $this->_from = new FromExpression();

        $this->fieldset = new SQLColumnSet();
        $this->whereset = new ClauseCollection();

        //copy the where clause collection
        if ($other) {
            $this->_from = clone $other->_from;
            $other->where()->copyTo($this->whereset);
            //copy bindings
            $this->externalBindings = $other->externalBindings;
        }

        $this->_limit = new LimitExpression();
    }

    public function __clone() : void
    {
        $this->whereset = clone $this->whereset;
        $this->fieldset = clone $this->fieldset;
        $this->_from = clone $this->_from;
        $this->_limit = clone $this->_limit;
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
            if (SQLStatement::IsBindingValueSafe($bindingValue)) {
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

    public function bind(string $bindingKey, string|int|float|bool|null $value) : void
    {
        if (!$bindingKey) throw new Exception("bindingKey is empty");
        $this->externalBindings[$bindingKey] = $value;
    }

    /**
     * Bind values and return unique binding key for each value - comma separated.
     *
     * Bind each value from the $list array to the internal statement bindings and return string suitable for using
     * inside IN and NOT IN SQL constructs.
     *
     * For each element a binding key is constructed like this : "L_".Spark::Hash($value)."_".$idx
     *
     * Return all binding keys comma separated.
     *
     * * \$keep_list = $delete->bindList([1,2,3,4,5]);
     * * \$delete->where()->addExpression("key NOT IN ($keep_list)");
     *
     * @param array<string|float|int|bool|null> $list
     * @return string
     * @throws Exception
     */
    public function bindList(array $list) : string
    {
        if (count($list) < 1) throw new Exception("list is empty");

        $idx = 0;
        $keysList = [];
        foreach ($list as $value) {
            if (!SQLStatement::IsBindingValueSafe($value)) throw new Exception("List element with incorrect binding value");
            $bindingKey = SQLStatement::FormatBindingKey("L_".Spark::Hash($value)."_".$idx);
            $keysList[] = $bindingKey;
            $this->externalBindings[$bindingKey] = $value;
            $idx++;
        }

        return implode(",", $keysList);
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
     * Validates whether the provided string is a valid PDO named parameter binding key.
     *
     * This method enforces that the binding key **must start with a colon (:)**,
     * as is conventional when calling bind() methods.
     *
     * Valid examples:
     *   - ":userId"
     *   - ":email"
     *   - ":limit_offset"
     *
     * Invalid examples:
     *   - "userId"        (missing colon)
     *   - ":123start"     (starts with digit after colon)
     *   - ":user-name"    (contains invalid character)
     *   - "" or ":"       (empty or only colon)
     *
     * @param string $bindName The binding key as typically passed to bind() — must start with ":"
     * @return bool
     */
    public static function IsBindingKeySafe(string $bindName): bool
    {
        // Must start with colon
        if (!str_starts_with($bindName, ':')) return false;

        // Extract the name part (everything after the colon)
        $key = substr($bindName, 1);

        // After removing colon: must not be empty
        if ($key === '') return false;

        // Practical length limit
        if (strlen($key) > 100) return false;

        // Must start with letter or underscore
        // Followed by letters, digits, underscores only
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key) === 1;
    }
    /**
     * Check if value is safe to be used as value during named parameter binding.
     * True if (is_scalar($value) || is_null($value) || is_array($value))
     * @param mixed $value
     * @return bool True if value is scalar, array or null
     */
    public static function IsBindingValueSafe(mixed $value) : bool
    {
        if ( is_scalar($value) || is_null($value) ) return true;
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