<?php
include_once("sql/SQLStatement.php");
include_once("sql/HavingExpression.php");
include_once("sql/traits/CanModifyColumnName.php");
include_once("sql/traits/CanSetColumnAliasExpression.php");
include_once("sql/traits/CanSetLimitWithOffset.php");
include_once("sql/traits/CanSetOrder.php");
include_once("sql/traits/CanUseFromExpression.php");
include_once("sql/traits/CanUseHavingExpression.php");

class SQLSelect extends SQLStatement
{

    use CanModifyColumnName;
    use CanSetColumnAliasExpression;
    use CanSetLimitWithOffset;
    use CanSetOrder;

    use CanUseFromExpression;
    use CanUseHavingExpression;

    const int SQL_CALC_FOUND_ROWS = 1;
    const int SQL_CACHE = 2;
    const int SQL_NO_CACHE = 3;

    protected array $modeMask = array();

    protected ?HavingExpression $_having = null;

    public static function Table(string $tableName) : SQLSelect
    {
        $result = new SQLSelect();
        $result->_from->expr($tableName);
        return $result;
    }

    public function __construct(?SQLStatement $other = NULL)
    {
        parent::__construct($other);
        $this->type = "SELECT";
        $this->_having = new HavingExpression();
    }

    public function __clone() : void
    {
        parent::__clone();
        //not shared between the clones - combine() appends to it
        $this->_having = clone $this->_having;
    }

    public function clearMode() : void
    {
        $this->modeMask = array();
    }

    public function setMode(int $mode_clause) : void
    {
        $this->modeMask[$mode_clause] = 1;
    }

    public function unsetMode(int $mode_clause) : void
    {
        if (isset($this->modeMask[$mode_clause])) {
            unset($this->modeMask[$mode_clause]);
        }
    }

    public function haveMode(int $mode_clause) : bool
    {
        return isset($this->modeMask[$mode_clause]);
    }

    public function getSQL() : string
    {
        if ($this->_from->isEmpty()) throw new Exception("Empty FROM expression");
        if ($this->fieldset->count() < 1) throw new Exception("Empty fieldset");

        $sql = $this->type . " ";

        if (isset($this->modeMask[SQLSelect::SQL_CALC_FOUND_ROWS])) {
            $sql .= " SQL_CALC_FOUND_ROWS ";
        }
        if (isset($this->modeMask[SQLSelect::SQL_CACHE])) {
            $sql .= " SQL_CACHE ";
        }
        if (isset($this->modeMask[SQLSelect::SQL_NO_CACHE])) {
            $sql .= " SQL_NO_CACHE ";
        }

        $sql .= $this->fieldset->getSQL();

        $sql .= " FROM $this->_from ";

        if ($this->whereset->count()>0) {
            $sql.=" WHERE ".$this->whereset->getSQL();
        }

        if (strlen(trim($this->group_by)) > 0) {
            $sql .= " GROUP BY " . $this->group_by . " ";
        }

        if (!$this->_having->isEmpty()) {
            $sql .= " HAVING " . $this->_having;
        }

        $sql .= $this->getOrderSQL();

        $sql .= $this->_limit->getSQL();

        return $sql;
    }

    /**
     * Combine/Copy properties from other to '$this'
     * Merge/replace $this->externalBindings with $other->externalBindings
     * @param SQLSelect $other
     * @return void
     * @throws Exception
     */
    public function combine(SQLSelect $other) : void
    {

        if ($other->fieldset->count() > 0) {
            $other->fieldset->copyTo($this->fieldset);
        }

        if (strlen(trim($other->_from)) > 0) {
            //join expressions are appended, table lists are separated with ','
            if (preg_match('/^(join|straight_join|(left|right|inner|cross|natural)(\s+outer)?\s+join)\b/i', trim($other->_from))) {
                if (strlen(trim($this->_from))) {
                    $this->_from->append($other->_from);
                }
                else {
                    $this->_from = $other->_from;
                }
            }
            else {
                if (strlen(trim($this->_from))) {
                    $this->_from->append(" , " . $other->_from);
                }
                else {
                    $this->_from = $other->_from;
                }
            }
        }

        $other->whereset->copyTo($this->whereset);

        if (strlen(trim($this->group_by)) > 0) {
            if (strlen(trim($other->group_by)) > 0) {
                $this->group_by .= " , " . $other->group_by;
            }
        }
        else if (strlen(trim($other->group_by)) > 0) {
            $this->group_by .= $other->group_by;
        }

        if (!$other->_having->isEmpty()) {
            $this->_having->and($other->_having);
        }


        //combine and replace. all ordercolumns from other get into this order columns
        $other->copyOrderTo($this->orderParameters);

        //Limit can not be combined. During pagination the main select is combined with the paged

        SQLStatement::ReplaceKeyAppend($this->externalBindings, $other->getBindings());
    }

    /**
     * Get all bindings from $other and assign to this
     * @param SQLSelect $other
     * @return void
     * @throws Exception
     */
    public function collectBindings(SQLSelect $other): void
    {
        SQLStatement::ReplaceKeyAppend($this->externalBindings, $other->getBindings());
    }

    public function combineWith(SQLSelect $other) : SQLSelect
    {
        $csql = clone $this;

        $csql->combine($other);

        return $csql;
    }

    /**
     * Return new SQLSelect selecting this as a derived table '$as_name' default "relation".
     * External bindings are copied to the resulting SQLSelect.
     *
     * @param string $as_name
     * @return SQLSelect
     * @throws Exception
     */
    public function getAsDerived(string $as_name="relation") : SQLSelect
    {
        $tsel = clone $this;
        $tsel->clearMode();

        $sel = SQLSelect::Table(" (".$tsel->getSQL().") AS $as_name ");

        SQLStatement::ReplaceKeyAppend($sel->externalBindings, $this->getBindings());

        return $sel;
    }

    /**
     * Deferred join ("late row lookup").
     * The inner query does the filtering, joining, grouping, ordering and limiting reading only the key of
     * '$lookupTable', the outer query joins '$lookupTable' back by the key and reads the selected columns
     * for the found rows only.
     *
     *  SELECT <columns> FROM (SELECT t.key FROM ... WHERE ... ORDER BY ... LIMIT ...) AS lookup
     *  STRAIGHT_JOIN t ON t.key = lookup.key ORDER BY ...
     *
     * The selected columns must be readable from '$lookupTable' - qualify them with the table name.
     * The order is applied to both queries: the inner one selects the page, the outer one keeps it in
     * order - a derived table does not keep its order through the join. Order names are resolved by
     * MySQL against the selected columns first, so in the inner query only the key is visible by name,
     * in the outer query all the selected columns.
     *
     * @param string $lookupTable
     * @param string $lookupKey
     * @return SQLSelect
     * @throws Exception
     */
    public function lateLookup(string $lookupTable, string $lookupKey) : SQLSelect
    {
        $lookup = clone $this;
        $lookup->columns()->reset();
        $lookup->columns($lookupTable.".".$lookupKey);

        //outer select
        $stmt = new SQLSelect();
        //copy the heavy columns to the outside lookup query
        $this->columns()->copyTo($stmt->columns());

        $stmt->from("(".$lookup->getSQL().") as lookup")->straightJoin($lookupTable)->on("$lookupTable.$lookupKey = lookup.$lookupKey");

        //keep the order of the inner query
        $this->copyOrderTo($stmt->orderParameters);

        $stmt->collectBindings($this);

        return $stmt;
    }
}