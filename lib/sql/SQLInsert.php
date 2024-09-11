<?php
include_once("sql/SQLStatement.php");

class SQLInsert extends SQLStatement
{

    public function __construct()
    {
        parent::__construct();
        $this->type = "INSERT INTO";
    }

    public function getSQL() : string
    {

        $sql = $this->type . " " . $this->from;
        $sql .= "(" . implode(",", array_keys($this->set)) . ")";
        $sql .= " VALUES ";


        $keys = array_keys($this->set);
        $values = array_values($this->set);
        //debug("Values contents: ".print_r($values, true));

        if (is_array($values[0])) {
            //if value is array count should be equal for each element in the set
            $values_count = count($values[0]);

            foreach ($keys as $key) {
                if (!is_array($this->set[$key]) || count($this->set[$key])!=$values_count) {
                    throw new Exception("Values count should be equal for each column in the set");
                }
            }

            $values_sql = array();
            for ($idx = 0; $idx < $values_count; $idx++) {
                $row = array();
                //foreach column in the set
                foreach ($values as $value) {
                    $row[] = $value[$idx];
                }
                $values_sql[] = $this->prepareValues($row);
            }
            $sql.= implode(",", $values_sql);
        }
        else {
            $sql .= $this->prepareValues($values);
        }

        return $sql;
    }

    /**
     * SQL Encode values
     * @param array $values
     * @return string
     */
    protected function prepareValues(array $values) : string
    {
        return "(" . implode(",", $values) . ")";
    }

    /**
     * Append value for multi-value insert to column '$column'
     * No quoting or escaping is done
     * @param string $column
     * @param string $value
     * @return void
     */
    public function setAppend(string $column, string $value) : void
    {
        $this->set[$column][] = $value;

    }

}
?>
