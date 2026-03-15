<?php
include_once("sql/SQLClause.php");
include_once("objects/SparkList.php");
include_once("sql/IBindingCollection.php");
/**
 * SQL Where clause collection
 */
class ClauseCollection extends SparkList implements ISQLGet, IBindingCollection
{

    public function __construct()
    {
        parent::__construct();
    }

    public function append(SparkObject $object) : void
    {
        if (!($object instanceof SQLClause)) throw new Exception("Incorrect object for this collection");
        //if ($clause->equals($clause_existing)) {

        if ($this->contains($object)) {
            Debug::ErrorLog("Clause already exists: " . $object->getSQL());
            return;
        }
        parent::append($object);
    }


    public function addURLParameter(URLParameter $param): ClauseCollection
    {
        return $this->add($param->name(), $param->value(TRUE));
    }

    /**
     * Create new SQLClause and append it to this collection.
     *
     * $clause->setExpression(name '$name', value '$value', operator '$operator')
     * $clause->setGlue('$glue')
     *
     * By design SQLClause create a bindingKey if '$name' and '$value' are not empty.
     *
     * @param string $name
     * @param string $value
     * @param string $operator
     * @param string $glue
     * @return $this
     * @throws Exception
     */
    public function add(string $name, array|string|float|int|bool|null $value, string $operator = SQLClause::DEFAULT_OPERATOR, string $glue = SQLClause::DEFAULT_GLUE): ClauseCollection
    {
        $clause = new SQLClause();
        $clause->setExpression($name, $value);
        $clause->setOperator($operator);
        $clause->setGlue($glue);

        $this->append($clause);

        return $this;
    }

    /**
     * Create new SQLClause without value so no automatic binding is done
     *
     * @param string $expression
     * @param string $glue
     * @return $this
     * @throws Exception
     */
    public function addExpression(string $expression, string $glue = SQLClause::DEFAULT_GLUE) : ClauseCollection
    {
        return $this->add($expression, "", "", $glue);
    }

    public function removeExpression(string $expression) : void
    {
        foreach ($this->elements as $idx=>$clause) {
            if (!($clause instanceof SQLClause))continue;
            if (strcmp($clause->getExpression(), $expression)===0) {
                unset($this->elements[$idx]);
            }
        }

    }

    public function copyTo(ClauseCollection $other) : void
    {
        $iterator = $this->iterator();
        while($clause = $iterator->next()) {
            $other->append($clause);
        }

    }

    public function getSQL() : string
    {
        if ($this->count() <1) return "";
        $result = "";

        $last_clause = NULL;
        $iterator = $this->iterator();
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

        $iterator = $this->iterator();
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

        return $result;
    }
}