<?php
include_once("forms/InputForm.php");

class KeywordSearchForm extends InputForm
{

    protected $table_fields = NULL;
    protected $search_expressions = NULL;

    public function __construct(array $table_fields)
    {
        parent::__construct();
        $this->table_fields = $table_fields;
        $this->search_expressions = array();

        $field = new DataInput("keyword", "Keyword", 0);
        new TextField($field);
        $this->addInput($field);

    }

    public function setCompareExpression(string $field_name, array $expressions)
    {
        $this->search_expressions[$field_name] = $expressions;
    }

    public function setSearchFields(array $table_fields)
    {
        $this->table_fields = $table_fields;
    }

    public function getSearchFields()
    {
        return $this->table_fields;
    }

    protected function searchFilterForKey(string $key, string $val): string
    {

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

            return "( " . implode(" AND ", $qry) . " )";
        }
        else {
            return parent::searchFilterForKey($key, $val);
        }
    }

    public function clearQuery(array &$qryarr)
    {
        foreach ($this->inputs as $field_name => $field) {
            if (isset($qryarr[$field_name])) {
                unset($qryarr[$field_name]);
            }
        }
    }
}

?>
