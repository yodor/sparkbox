<?php
include_once("forms/InputForm.php");

class KeywordSearchForm extends InputForm
{

    /**
     * @var array
     */
    protected array $queryColumns = array();

    public function __construct()
    {
        parent::__construct();

        $keyword = DataInputFactory::Create(InputType::TEXT, "keyword", "Keyword", 0);
        $this->addInput($keyword);
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
        if (strcmp($key, "keyword")!==0) {
            return parent::clauseValue($key, $val);
        }

        $clause = new SQLClause();

        //split input into space delimited keywords
        $allWords = explode(" ", $val);

        $resultAll = array();

        foreach ($allWords as $idx => $keyword) {
            $result = array();
            foreach ($this->queryColumns as $idx1 => $column) {
                $result[] = " $column LIKE :keyword ";
            }
            $resultAll[] = "( " . implode(" OR ", $result) . " )";
        }
        $expr = "( " . implode(" AND ", $resultAll) . " )";

        //'%$keyword%'
        //set expression only - no automatic binding key, clear hasValue and operator
        //bind the value
        //ie getSQL for this clause return ( $column LIKE :keyword) and during binding collection :keyword=>%$keyword%
        $clause->setExpression($expr);
        $clause->bind(":keyword", "%$keyword%");
        return $clause;

    }

}