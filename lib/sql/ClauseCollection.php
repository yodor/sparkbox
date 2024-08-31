<?php
include_once("sql/SQLClause.php");

class ClauseCollection
{

    protected array $clauses;

    public function __construct()
    {
        $this->clauses = array();
    }

    public function __clone()
    {
        foreach ($this->clauses as $idx => $clause) {
            $this->clauses[$idx] = clone $clause;
        }
    }

    public function clear() {
        $this->clauses = array();
    }

    public function getAll() : array
    {
        return $this->clauses;
    }
    public function iterator() : ArrayIterator
    {
        return new ArrayIterator($this->clauses);
    }
    public function keys() : array
    {
        return array_keys($this->clauses);
    }

    public function count(): int
    {
        return count($this->clauses);
    }

    public function addClause(SQLClause $clause)
    {
        foreach ($this->clauses as $clause_existing) {
            if (!($clause_existing instanceof SQLClause))continue;

            if ($clause->equals($clause_existing)) {
                debug("Clause already exists: ".$clause->getSQL());
                return;
            }
        }

        $this->clauses[] = $clause;
    }

    /**
     * Get clause at position '$idx'
     * @param int $idx
     * @return SQLClause
     */
    public function get(int $idx): SQLClause
    {
        return $this->clauses[$idx];
    }

    public function addURLParameter(URLParameter $param): ClauseCollection
    {
        return $this->add($param->name(), $param->value(TRUE));
    }

    /**
     * Add clause to this clause collection
     * @param string $name
     * @param string $value
     * @param string $operator
     * @param string $glue
     * @return $this
     */
    public function add(string $name, string $value, string $operator = SQLClause::DEFAULT_OPERATOR, string $glue = SQLClause::DEFAULT_GLUE): ClauseCollection
    {
        $clause = new SQLClause();
        $clause->setExpression($name, $value, $operator);
        $clause->setGlue($glue);

        $this->addClause($clause);

        return $this;
    }

    /**
     * @param string $expression The expresion part of this clause collection to search for
     * @return SQLClause|null The sqlclause that was removed or null if expression is not found in any of the clauses in this collection
     */
    public function remove(string $expression) : ?SQLClause
    {
        $ret = null;

        foreach ($this->clauses as $idx=>$clause) {
            if (!($clause instanceof SQLClause))continue;
            if (strcmp($clause->getExpression(), $expression)==0) {
                $ret = $clause;
                unset($this->clauses[$idx]);
            }
        }

        return $ret;
    }

    /**
     * Append the expression to this clause collection using $glue as operator 
     * @param string $expression
     * @param string $glue
     * @return $this
     */
    public function append(string $expression, string $glue = SQLClause::DEFAULT_GLUE): ClauseCollection
    {
        return $this->add($expression, "", "", $glue);
    }

    public function copyTo(ClauseCollection $other)
    {
        $keys = array_keys($this->clauses);

        foreach ($keys as $pos) {
            $clause = $this->get($pos);
            $other->addClause($clause);
        }
    }

    public function getSQL($with_where_text = TRUE): string
    {
        $result = "";

        if ($this->count() > 0) {

            if ($with_where_text) {
                $result .= " WHERE ";
            }

            $last_clause = NULL;

            $keys = array_keys($this->clauses);

            foreach ($keys as $pos) {
                $clause = $this->get($pos);
                if ($last_clause instanceof SQLClause) {
                    $result .= " " . $last_clause->getGlue() . " ";
                }
                $result .= $clause->getSQL();
                $last_clause = $clause;
            }

        }
        return $result;
    }
}

?>
