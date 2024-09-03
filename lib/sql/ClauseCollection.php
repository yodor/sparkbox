<?php
include_once("sql/SQLClause.php");
include_once("objects/SparkList.php");

/**
 * SQL Where clause collection
 */
class ClauseCollection extends SparkList implements ISQLGet
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
            debug("Clause already exists: ".$object->getSQL());
            return;
        }
        parent::append($object);
    }


    public function addURLParameter(URLParameter $param): ClauseCollection
    {
        return $this->add($param->name(), $param->value(TRUE));
    }

    public function add(string $name, string $value, string $operator = SQLClause::DEFAULT_OPERATOR, string $glue = SQLClause::DEFAULT_GLUE): ClauseCollection
    {
        $clause = new SQLClause();
        $clause->setExpression($name, $value, $operator);
        $clause->setGlue($glue);

        $this->append($clause);

        return $this;
    }

    public function addExpression(string $expression, string $glue = SQLClause::DEFAULT_GLUE) : ClauseCollection
    {
        return $this->add($expression, "", "", $glue);
    }

    public function removeExpression(string $expression) : void
    {
        foreach ($this->elements as $idx=>$clause) {
            if (!($clause instanceof SQLClause))continue;
            if (strcmp($clause->getExpression(), $expression)==0) {
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

    public function getSQL(): string
    {
        $result = "";

        if ($this->count() <1) return $result;

        $result .= " WHERE ";

        $last_clause = NULL;
        $iterator = $this->iterator();
        while ($object = $iterator->next()) {
            if (!($object instanceof SQLClause))continue;
            //skip gluing of first clause
            if (!is_null($last_clause)) {
                $result .= " " . $last_clause->getGlue() . " ";
            }
            $result .= $object->getSQL();
            $last_clause = $object;
        }

        return $result;
    }
}

?>
