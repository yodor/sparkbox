<?php
include_once("sql/SQLStatement.php");

class SQLInsert extends SQLStatement
{

    public function __construct(?SQLStatement $other=null)
    {
        parent::__construct($other);
        $this->type = "INSERT INTO";
    }

    public function getSQL() : string
    {

        $sql = $this->type . " " . $this->from;
        $sql .= "(" . implode(",", $this->fieldset->names()) . ")";
        $sql .= " VALUES ";

        $values = $this->fieldset->values();

        //Debug::ErrorLog("Values contents: ".print_r($values, true));

        $multi_values = 0;
        foreach ($values as $name=>$value) {
            if (is_array($value)) {
                $multi_values = count($value);
                break;
            }
        }

        if ($multi_values>0) {
            Debug::ErrorLog("Multivalued insert - values count: $multi_values");

            foreach ($values as $name => $value) {
                if (!is_array($value) || (count($value)!=$multi_values)) {
                    throw new Exception("Column '$name' values count mismatch. Should be equal for each column in the set");
                }
            }

            $values_sql = array();
            for ($idx = 0; $idx < $multi_values; $idx++) {
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


}