<?php
include_once("forms/InputForm.php");

class KeywordSearchForm extends InputForm
{

    protected $ts_fields = NULL;
    protected $search_expressions = NULL;

    public function __construct(array $table_fields)
    {
        parent::__construct();
        $this->ts_fields = $table_fields;
        $this->search_expressions = array();

        $field = new DataInput("keyword", "Keyword", 0);
        new TextField($field);
        $this->addInput($field);

    }

    public function setCompareExpression($field_name, array $expressions)
    {
        $this->search_expressions[$field_name] = $expressions;
    }

    public function setSearchFields(array $table_fields)
    {
        $this->ts_fields = $table_fields;
    }

    public function getSearchFields()
    {
        return $this->ts_fields;
    }

    protected function searchFilterForKey(string $key, string $val)
    {
        $db = DBDriver::Get();
        $val = $db->escapeString($val);
        if (strcmp($key, "keyword") == 0) {
            $allwords = explode(" ", $val);

            $qry = array();

            foreach ($allwords as $pos => $keyword) {
                $ret = array();
                foreach ($this->ts_fields as $pos1 => $field_name) {

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

    public function clearQuery(&$qryarr)
    {
        foreach ($this->inputs as $field_name => $field) {
            if (isset($qryarr[$field_name])) {
                unset($qryarr[$field_name]);
            }
        }
        unset($qryarr["clear"]);

    }
}

?>
