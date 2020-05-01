<?php
include_once("lib/utils/SQLStatement.php");

class SQLSelect extends SQLStatement
{

    public function __construct()
    {
        $this->type = "SELECT ";
    }

    public function getSQL($where_only = false, $add_calc = true)
    {
        $sql = "";

        if ($where_only) {
            //
        }
        else {
            if ($add_calc) {
                $sql .= $this->type . " SQL_CALC_FOUND_ROWS {$this->fields} FROM {$this->from} ";
            }
            else {
                $sql .= $this->type . "  {$this->fields} FROM {$this->from} ";
            }
        }

        if (strlen(trim($this->where)) > 0) {
            $sql .= " WHERE " . $this->where . " ";
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

        if (strlen(trim($other->fields)) > 0) {

            if (strlen($this->fields) > 0) {
                $this->fields .= " , ";
            }
            $this->fields .= $other->fields;

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


        if (strlen(trim($this->where)) > 0) {
            if (strlen(trim($other->where)) > 0) {
                $this->where = $this->where . " AND " . $other->where;
            }
        }
        else {
            $this->where = $other->where;
        }

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

    public function combineSection($section, $where)
    {
        //$where = " parent.parentID='$parentID' ";
        if (strlen(trim($this->$section)) > 0) {
            $this->$section .= " AND $where";
        }
        else {
            $this->$section = $where;
        }
    }
}

?>
