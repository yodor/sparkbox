<?php
include_once("forms/InputForm.php");

class KeywordSearchForm extends InputForm
{

    /**
     * @var array
     */
    protected $table_fields;

    protected $search_expressions = NULL;
    protected $compare_operators = NULL;

    public function __construct()
    {
        parent::__construct();

        $this->table_fields = array();

        $this->search_expressions = array();

        $this->compare_operators = array();

        $field = new DataInput("keyword", "Keyword", 0);
        new TextField($field);
        $this->addInput($field);

    }

    public function setCompareExpression(string $field_name, array $expressions, string $compare_operator = "LIKE")
    {
        $this->search_expressions[$field_name] = $expressions;
        $this->compare_operators[$field_name] = $compare_operator;
    }

    public function setFields(array $table_fields)
    {
        $this->table_fields = $table_fields;
    }

    public function removeField(string ...$names)
    {
        foreach ($this->table_fields as $idx=>$name)
        {
            if (in_array($name, $names)) unset($this->table_fields[$idx]);
        }

    }

    public function getFields(): array
    {
        return $this->table_fields;
    }

    protected function clauseValue(string $key, string $val): SQLClause
    {
        $clause = new SQLClause();

        $val = DBConnections::Open()->escape($val);
        if (strcmp($key, "keyword") == 0) {

            $allwords = explode(" ", $val);

            $qry = array();

            foreach ($allwords as $pos => $keyword) {

                $ret = array();

                foreach ($this->table_fields as $pos1 => $field_name) {

                    if (isset($this->search_expressions[$field_name])) {
                        foreach ($this->search_expressions[$field_name] as $idx => $expression) {
                            $expression = str_replace("{keyword}", $keyword, $expression);
                            $operator = $this->compare_operators[$field_name];
                            $ret[] = " $field_name $operator '$expression' ";
                        }
                    }
                    else {

                        $ret[] = " $field_name LIKE '%$keyword%' ";

                    }

                }

                $qry[] = "( " . implode(" OR ", $ret) . " )";

            }
            $expr = "( " . implode(" AND ", $qry) . " )";

            //set expression directly
            $clause->setExpression($expr, "", "");
            return $clause;
        }
        else {
            return parent::clauseValue($key, $val);
        }
    }

}

?>