<?php
include_once("sql/SQLStatement.php");

class SQLInsert extends SQLStatement
{
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
    protected function rowToSQL(bool $do_prepared, int $idx, int $totalRows): string
    {
        $placeholders = array();
        foreach ($this->fieldset->names() as $name) {
            $column = $this->fieldset->getColumn($name);

            if ($column->getExpression()) {
                $placeholders[] = $column->getExpression();
                continue;
            }

            if ($do_prepared) {
                $key = $column->getBindingKey();
                // Use indexed keys only if we have multiple rows
                $placeholders[] = ($totalRows > 1) ? $key . "_" . $idx : $key;
            } else {
                $val = $column->getValueAtIndex($idx);
                $placeholders[] = is_null($val) ? 'NULL' : $val;
            }
        }
        return $this->wrapBrackets($placeholders);
    }

    public function collectSQL(bool $do_prepared): string
    {
        $rowCount = $this->fieldset->getRowCount();
        if ($rowCount < 1) {
            throw new Exception("No data provided for INSERT");
        }

        $sql = $this->type . " " . $this->from . " ";
        $sql .= $this->wrapBrackets($this->fieldset->names()) . " VALUES ";

        $rows = array();
        for ($i = 0; $i < $rowCount; $i++) {
            $rows[] = $this->rowToSQL($do_prepared, $i, $rowCount);
        }

        return $sql . implode(", ", $rows);
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

    public function getSQL(): string { return $this->collectSQL(false); }
    public function getPreparedSQL(): string { return $this->collectSQL(true); }
}