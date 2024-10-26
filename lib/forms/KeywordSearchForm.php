<?php
include_once("forms/InputForm.php");

class KeywordSearchForm extends InputForm
{

    /**
     * @var array
     */
    protected array $queryColumns = array();

    protected array $searchExpressions = array();
    protected array $compareOperators = array();

    public function __construct()
    {
        parent::__construct();

        $keyword = new DataInput("keyword", "Keyword", 0);
        new TextField($keyword);
        $this->addInput($keyword);
    }

    public function setCompareExpression(string $column, array $expressions, string $compare_operator = "LIKE"): void
    {
        $this->searchExpressions[$column] = $expressions;
        $this->compareOperators[$column] = $compare_operator;
    }

    public function setColumns(array $queryColumns): void
    {
        $this->queryColumns = $queryColumns;
    }

    public function removeColumns(string ...$names): void
    {
        foreach ($this->queryColumns as $idx => $name)
        {
            if (in_array($name, $names)) unset($this->queryColumns[$idx]);
        }
    }

    public function getColumns(): array
    {
        return $this->queryColumns;
    }

    protected function clauseValue(string $key, string $val): SQLClause
    {
        if (strcmp($key, "keyword")!=0) {
            return parent::clauseValue($key, $val);
        }

        $clause = new SQLClause();

        $db = DBConnections::Open();

        $allWords = explode(" ", $db->escape($val));

        $resultAll = array();

        foreach ($allWords as $idx => $keyword) {

            $result = array();

            foreach ($this->queryColumns as $idx1 => $column) {

                if (isset($this->searchExpressions[$column])) {

                    foreach ($this->searchExpressions[$column] as $idx2 => $expression) {

                        $expression = str_replace("{keyword}", $keyword, $expression);
                        $operator = $this->compareOperators[$column];
                        $result[] = " $column $operator '$expression' ";

                    }

                }
                else {

                    $result[] = " $column LIKE '%$keyword%' ";

                }

            }

            $resultAll[] = "( " . implode(" OR ", $result) . " )";

        }
        $expr = "( " . implode(" AND ", $resultAll) . " )";

        //set expression directly
        $clause->setExpression($expr, "", "");
        return $clause;

    }

}

?>