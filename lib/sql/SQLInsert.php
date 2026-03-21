<?php
include_once("sql/SQLStatement.php");
include_once("sql/CanSetColumnValue.php");
include_once("sql/CanCreateArrayValueColumn.php");
include_once("sql/CanAccessColumnsDirectly.php");

class SQLInsert extends SQLStatement
{
    use CanSetColumnValue;
    use CanCreateArrayValueColumn;

    /**
     * ON Clause Value
     * @var string
     */
    protected string $on = "";

    static public function Table(string $tableName) : SQLInsert
    {
        $result = new SQLInsert();
        $result->_from->expr($tableName);
        return $result;
    }

    public function __construct(?SQLStatement $other = null)
    {
        parent::__construct($other);
        $this->type = "INSERT INTO";
    }

    protected function wrapBrackets(array $input): string
    {
        return "(" . implode(", ", $input) . ")";
    }

    /**
     * Generates values fragment for a specific row.
     */
    protected function rowToSQL(int $idx, int $totalRows): string
    {
        $placeholders = array();
        foreach ($this->fieldset->names() as $name) {

            $column = $this->fieldset->getColumn($name);

            if ($column->getExpression()) {
                $placeholders[] = $column->getExpression();
                continue;
            }

            $key = $column->getBindingKey();
            // Use indexed keys only if we have multiple rows
            $placeholders[] = ($totalRows > 1) ? $key . "_" . $idx : $key;

        }
        return $this->wrapBrackets($placeholders);
    }

    public function getSQL(): string
    {
        $rowCount = $this->getRowCount();

        if ($rowCount < 1) {
            throw new Exception("No data provided for INSERT");
        }

        $sql = $this->type . " " . $this->_from . " ";
        $sql .= $this->wrapBrackets($this->fieldset->names()) . " VALUES ";

        $rows = array();

        for ($i = 0; $i < $rowCount; $i++) {
            $rows[] = $this->rowToSQL($i, $rowCount);
        }

        $sql = $sql . implode(", ", $rows);

        if (strlen(trim($this->on)) > 0) {
            $sql .= " ON ".$this->on;
        }

        return $sql;
    }

    public function getBindings(): array
    {
        $rowCount = $this->getRowCount();

        $bindings = array();

        for ($i = 0; $i < $rowCount; $i++) {
            foreach ($this->fieldset->names() as $name) {
                $column = $this->fieldset->getColumn($name);
                $key = $column->getBindingKey();

                if ($key) {
                    $fullKey = ($rowCount > 1) ? $key . "_" . $i : $key;
                    $bindings[$fullKey] = $column->getValueAtIndex($i);
                }
            }
        }

        // Merge manual bind() calls (external bindings)
        SQLStatement::ReplaceKeyAppend($bindings, $this->externalBindings);

        return $bindings;
    }


    /**
     * Calculates the total number of rows based on the column with the most values.
     */
    public function getRowCount(): int
    {
        $maxRows = 0;
        foreach ($this->fieldset->names() as $idx => $columnName) {
            $column = $this->fieldset->getColumn($columnName);
            if (!$column->hasValue()) continue;

            $val = $column->getValue();
            if (is_array($val)) {
                $maxRows = max($maxRows, count($val));
            } else {
                $maxRows = max($maxRows, 1);
            }
        }
        return $maxRows;
    }
    public function on(string $expr) : void
    {
        $this->on = $expr;
    }

}