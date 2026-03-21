<?php
include_once("sql/SQLClause.php");
include_once("objects/SparkList.php");
include_once("sql/IBindingCollection.php");
include_once("objects/SparkMap.php");
include_once("sql/CanSetExternalBinding.php");
include_once("sql/CanSetExternalBindingList.php");

/**
 * SQL Where clause collection
 */
class ClauseCollection extends SparkObject implements ISQLGet, IBindingCollection, IBindingModifier
{

    use CanSetExternalBinding;
    use CanSetExternalBindingList;

    protected SparkMap $elements;

    public function __construct()
    {
        parent::__construct();
        $this->elements = new SparkMap();
    }

    public function __clone() : void
    {
        $this->elements = clone $this->elements;
    }

    public function iterator() : SparkIterator
    {
        return $this->elements->iterator();
    }

    public function append(SQLClause $object) : void
    {
        if ($this->elements->isSet($object->hash())) {
            Debug::ErrorLog("Clause hash exists - replacing clause: " . $object->getSQL());
        }

        $this->elements->add($object->hash(), $object);
    }

    public function count() : int
    {
        return $this->elements->count();
    }

    public function clear() : void
    {
        $this->elements->clear();
    }

    /**
     *
     * Create new column matching SQLClause using name and value from \$param URLParameter.
     * Proxy method for \$this->add()
     *
     * @param URLParameter $param
     * @param string $operator
     * @param string $glue
     * @return $this
     * @throws Exception
     */
    public function addURLParameter(URLParameter $param, string $operator = SQLClause::DEFAULT_OPERATOR, string $glue = SQLClause::DEFAULT_GLUE): ClauseCollection
    {
        return $this->add($param->name(), $param->value(), $operator, $glue);
    }

    /**
     * Create new column matching SQLClause and append it to this clause collection.
     * Automatic binding is created using name and value parameters.
     *
     * * \$clause->setExpression(\$name, \$value);
     * * \$clause->setOperator(\$operator);
     * * \$clause->setGlue(\$glue);
     *
     * @param string $name
     * @param string|float|int|bool|null $value
     * @param string $operator
     * @param string $glue
     * @return $this
     * @throws Exception If name or value is empty
     */
    public function add(string $name, string|float|int|bool|null $value, string $operator = SQLClause::DEFAULT_OPERATOR, string $glue = SQLClause::DEFAULT_GLUE): ClauseCollection
    {
        if (strlen(trim($name))<1) throw new Exception("Name cannot be empty");
        if (is_string($value) && strlen(trim($value))<1) throw new Exception("Value cannot be empty");

        $clause = new SQLClause();
        $clause->setExpression($name, $value);
        $clause->setOperator($operator);
        $clause->setGlue($glue);
        $this->append($clause);

        return $this;
    }

    /**
     * Create new expression matching SQLClause without value - so no automatic binding is done.
     *
     * * addExpression("stock_amount > 0"); -> no binding direct SQL expression
     *
     * If using expressions containing custom binding key an additional call to $collection->bind() should be
     * made to bind the required value.
     *
     * * ->addExpression("product_attributes LIKE :author_param");
     * * ->bind(":author_param", "%Author:$author_name%");
     *
     * @param string $expression
     * @param string $glue
     * @return $this
     * @throws Exception
     */
    public function addExpression(string $expression, string $glue = SQLClause::DEFAULT_GLUE) : ClauseCollection
    {
        $clause = new SQLClause();
        $clause->setExpression($expression);
        $clause->setGlue($glue);
        $clause->setOperator("");
        $this->append($clause);

        return $this;
    }

    public function removeExpression(string $expression) : void
    {
        $iterator = $this->elements->iterator();
        while ($clause = $iterator->next()) {
            if (!($clause instanceof SQLClause))continue;
            if (strcmp($clause->getExpression(), $expression)===0) {
                $this->elements->remove($iterator->key());
            }
        }

    }

    public function copyTo(ClauseCollection $other) : void
    {

        $iterator = $this->elements->iterator();
        while($clause = $iterator->next()) {

            if (!($clause instanceof SQLClause))continue;
            $other->append(clone $clause);

        }
        $this->copyExternaBindingsTo($other);

    }

    public function getSQL() : string
    {
        if ($this->elements->count() <1) return "";
        $result = "";

        $last_clause = NULL;
        $iterator = $this->elements->iterator();
        while ($object = $iterator->next()) {
            if (!($object instanceof SQLClause))continue;
            //skip gluing of first clause
            if (!is_null($last_clause)) {
                $result.= " " . $last_clause->getGlue() . " ";
            }
            $result.= $object->getSQL();
            $last_clause = $object;
        }

        return $result;
    }

    public function getBindings() : array
    {
        $result = array();

        $iterator = $this->elements->iterator();
        while ($object = $iterator->next()) {
            if (!($object instanceof ISQLBinding))continue;
            $bindingKey = $object->getBindingKey();
            if (!$bindingKey) continue;

            //result is :expr=>value
            $value = $object->getBindingValue();
            if (SQLStatement::IsBindingValueSafe($value)) {
                $result[$bindingKey] = $value;
            }
            else throw new Exception("[$bindingKey] value is not SQLStatement::IsBoundSafe");
        }

        SQLStatement::ReplaceKeyAppend($result, $this->externalBindings);

        return $result;
    }

}