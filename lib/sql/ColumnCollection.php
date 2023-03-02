<?php
include_once("sql/SQLCollection.php");

class ColumnCollection extends SQLCollection
{
    public function setExpression(string $expr, string $as)
    {
        $this->setValue($expr, $as);
    }

    public function getSQL()
    {
        $result = "";

        if ($this->count() > 0) {
            $fieldset_data = array();
            foreach ($this->fields as $key => $val) {
                $field = $key;
                if (strlen($val) > 0) {
                    $field .= " AS " . $val;
                }
                if ($this->prefix) {
                    $field = $this->prefix.".".$field;
                }
                $fieldset_data[] = $field;
            }
            $result = implode(" , ", $fieldset_data);
        }
        return $result;
    }

}

?>