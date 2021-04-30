<?php
include_once("sql/SQLStatement.php");
include_once("sql/ColumnCollection.php");

class SQLSelect extends SQLStatement
{

    //TODO
    protected $fieldset;

    public function __construct()
    {
        parent::__construct();
        $this->type = "SELECT";
        $this->fieldset = new ColumnCollection();
    }

    public function fields(): ColumnCollection
    {
        return $this->fieldset;
    }

    public function __clone()
    {
        parent::__clone();
        $this->fieldset = clone $this->fieldset;
    }

    public function getSQL($where_only = FALSE, $add_calc = TRUE)
    {
        if ( ($this->fieldset->count()<1) && (!$where_only)) {

            throw new Exception("Empty fieldset");
        }

        $sql = "";

        if ($where_only) {
            //
        }
        else {
            $sql .= $this->type." ";

            if ($add_calc) {
                $sql .= " SQL_CALC_FOUND_ROWS ";

            }

            //prefer fields from the fieldset
            if ($this->fieldset->count() > 0) {
                $sql .= $this->fieldset->getSQL();
            }

            $sql .= " FROM {$this->from} ";

        }

        if ($this->whereset->count()>0) {
            $sql.=$this->whereset->getSQL(true);
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

    public function combine(SQLSelect $other)
    {

        if ($other->fields()->count() > 0) {
            $other->fields()->copyTo($this->fieldset);
        }

        if (strlen(trim($other->from)) > 0) {
            $check = strtolower(trim($other->from));
            if (strpos($check, "join") === 0 || strpos($check, "left join") === 0 || strpos($check, "right join") === 0 || strpos($check, "inner join") === 0) {
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

        $sel->from = " (".$this->getSQL(false, false).") AS $as_name ";

        return $sel;
    }
}

?>