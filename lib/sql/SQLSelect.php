<?php
include_once("sql/SQLStatement.php");
include_once("sql/SQLColumnSet.php");
include_once("sql/CanModifyColumnName.php");
include_once("sql/CanSetColumnAliasExpression.php");
include_once("sql/CanSetLimitWithOffset.php");
include_once("sql/CanSetOrder.php");
include_once("sql/CanUseFromExpression.php");


class SQLSelect extends SQLStatement
{

    use CanModifyColumnName;
    use CanSetColumnAliasExpression;
    use CanSetLimitWithOffset;
    use CanSetOrder;
    use CanUseFromExpression;

    const int SQL_CALC_FOUND_ROWS = 1;
    const int SQL_CACHE = 2;
    const int SQL_NO_CACHE = 3;

    protected array $modeMask = array();

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

        if (strlen(trim($this->having)) > 0) {
            $sql .= " HAVING " . $this->having;
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
            $check = strtolower(trim($other->_from));
            if (str_starts_with($check, "join") ||
                str_starts_with($check, "left join") ||
                str_starts_with($check, "right join") ||
                str_starts_with($check, "inner join")) {
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

        if (strlen(trim($this->having)) > 0) {
            if (strlen(trim($other->having)) > 0) {
                $this->having .= " AND " . $other->having;
            }
        }
        else if (strlen(trim($other->having)) > 0) {
            $this->having .= $other->having;
        }

        //combine and replace. all ordercolumns from other get into this order columns
        $other->copyOrderTo($this->orderParameters);

        //Limit can not be combined. During pagination the main select is combined with the paged
//        if ($other->isLimited()) {
//            //do not set pagination limit if there is already limit in the select set
//            if ($this->isLimited()) {
//
//            }
//            else {
//                $this->limit($other->count, $other->offset);
//            }
//        }

        SQLStatement::replaceKeyAppend($this->externalBindings, $other->getBindings());
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

        SQLStatement::replaceKeyAppend($sel->externalBindings, $this->getBindings());

        return $sel;
    }

}