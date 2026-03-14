<?php
include_once("sql/SQLStatement.php");

class SQLInsert extends SQLStatement
{
    /**
     * ON Clause Value
     * @var string
     */
    public string $on = "";

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
        $rowCount = $this->fieldset->getRowCount();
        if ($rowCount < 1) {
            throw new Exception("No data provided for INSERT");
        }

        $sql = $this->type . " " . $this->from . " ";
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
        $rowCount = $this->fieldset->getRowCount();
        $names = $this->fieldset->names();
        $bindings = array();

        for ($i = 0; $i < $rowCount; $i++) {
            foreach ($names as $name) {
                $column = $this->fieldset->getColumn($name);
                $key = $column->getBindingKey();

                if ($key) {
                    $fullKey = ($rowCount > 1) ? $key . "_" . $i : $key;
                    $bindings[$fullKey] = $column->getValueAtIndex($i);
                }
            }
        }

        // Merge manual bind() calls (external bindings)
        SQLStatement::replaceKeyAppend($bindings, $this->externalBindings);

        return $bindings;
    }

}