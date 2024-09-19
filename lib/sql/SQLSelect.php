<?php
include_once("sql/SQLStatement.php");
include_once("sql/SQLColumnSet.php");

class SQLSelect extends SQLStatement
{

    protected SQLColumnSet $fieldset;

    const SQL_CALC_FOUND_ROWS = 1;
    const SQL_CACHE = 2;
    const SQL_NO_CACHE = 3;

    protected array $modeMask = array();

    public function __construct(SQLStatement $other = NULL)
    {
        parent::__construct();
        $this->type = "SELECT";
        $this->fieldset = new SQLColumnSet();

        //copy table and where
        if ($other) {
            $this->from = $other->from;
            $other->where()->copyTo($this->whereset);
        }
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

    public function fields(): SQLColumnSet
    {
        return $this->fieldset;
    }

    public function __clone() : void
    {
        parent::__clone();
        $this->fieldset = clone $this->fieldset;
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

        $sql .= " FROM $this->from ";

        if ($this->whereset->count()>0) {
            $sql.=$this->whereset->getSQL();
        }

        if (strlen(trim($this->group_by)) > 0) {
            $sql .= " GROUP BY " . $this->group_by . " ";
        }
        if (strlen(trim($this->having)) > 0) {
            $sql .= " HAVING " . $this->having;
        }
        if (strlen(trim($this->order_by)) > 0) {
            $sql .= " ORDER BY " . $this->order_by . " ";
        }
        if (strlen(trim($this->limit)) > 0) {
            $sql .= " LIMIT " . $this->limit . " ";
        }

        return $sql;
    }

    public function combine(SQLSelect $other) : void
    {

        if ($other->fields()->count() > 0) {
            $other->fields()->copyTo($this->fieldset);
        }

        if (strlen(trim($other->from)) > 0) {
            $check = strtolower(trim($other->from));
            if (str_starts_with($check, "join") ||
                str_starts_with($check, "left join") ||
                str_starts_with($check, "right join") ||
                str_starts_with($check, "inner join")) {
                if (strlen(trim($this->from))) {
                    $this->from .= $other->from;
                }
                else {
                    $this->from = $other->from;
                }
            }
            else {
                if (strlen(trim($this->from))) {
                    $this->from .= " , " . $other->from;
                }
                else {
                    $this->from = $other->from;
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

        if (strlen(trim($this->order_by)) > 0) {
            if (strlen(trim($other->order_by)) > 0) {
                $this->order_by .= " , " . $other->order_by;
            }
        }
        else if (strlen(trim($other->order_by)) > 0) {
            $this->order_by .= $other->order_by;
        }

        if (strlen(trim($other->limit)) > 0) {
            $this->limit .= " " . $other->limit;
        }
    }

    public function combineWith(SQLSelect $other)
    {
        $csql = clone $this;

        $csql->combine($other);

        return $csql;
    }

    /**
     * Return new SQLSelect selecting this SQLSelect as a derived table
     * @param string $as_name
     * @return SQLSelect
     * @throws Exception
     */
    public function getAsDerived(string $as_name="relation") : SQLSelect
    {
        $sel = new SQLSelect();
        $tsel = clone $this;
        $tsel->clearMode();
        $sel->from = " (".$tsel->getSQL().") AS $as_name ";

        return $sel;
    }
}

?>
