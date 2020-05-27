<?php
include_once("forms/InputForm.php");

class KeywordSearchForm extends InputForm
{

    /**
     * @var array
     */
    protected $table_fields;

    protected $search_expressions = NULL;

    public function __construct()
    {
        parent::__construct();

        $this->table_fields = array();

        $this->search_expressions = array();

        $field = new DataInput("keyword", "Keyword", 0);
        new TextField($field);
        $this->addInput($field);

    }

    public function setCompareExpression(string $field_name, array $expressions)
    {
        $this->search_expressions[$field_name] = $expressions;
    }

    public function setFields(array $table_fields)
    {
        $this->table_fields = $table_fields;
    }

    public function getFields(): array
    {
        return $this->table_fields;
    }

    protected function clauseValue(string $key, string $val): SQLClause
    {
        $clause = new SQLClause();

        $val = DBConnections::Get()->escape($val);
        if (strcmp($key, "keyword") == 0) {

            $allwords = explode(" ", $val);

            $qry = array();

            foreach ($allwords as $pos => $keyword) {

                $ret = array();

                foreach ($this->table_fields as $pos1 => $field_name) {

                    if (isset($this->search_expressions[$field_name])) {
                        foreach ($this->search_expressions[$field_name] as $idx => $expression) {
                            $expression = str_replace("{keyword}", $keyword, $expression);
                            $ret[] = " $field_name LIKE '$expression' ";
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
