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

    protected function appendClause(DataInput $input, ClauseCollection $collection, string $glue = SQLClause::DEFAULT_GLUE): void
    {
        if (strcmp($input->getName(), "keyword")!==0) {
            parent::appendClause($input, $collection, $glue);
            return;
        }

        $value = $input->getValue();

        if (strlen(trim($value))<1) {
            return;
        }

        //split input into space delimited keywords
        $allWords = explode(" ", $value);
        if (count($allWords)<1) return;

        $clause = new SQLClause();

        $resultAll = array();

        foreach ($allWords as $idx => $keyword) {
            $result = array();
            foreach ($this->queryColumns as $idx1 => $column) {
                $bindingKey = ":keyword_{$idx}_{$column}";
                $result[] = " $column LIKE $bindingKey";
                Debug::ErrorLog("Adding custom binding to clause: $bindingKey => %$keyword%");
                $collection->bind($bindingKey, "%$keyword%");
            }
            $resultAll[] = "( " . implode(" OR ", $result) . " )";
        }
        $expr = "( " . implode(" AND ", $resultAll) . " )";

        //set expression only - no automatic binding key, clear hasValue and operator
        $clause->setExpression($expr);

        $collection->append($clause);

    }

}