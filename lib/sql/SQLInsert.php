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
        return "(" . implode(",", $input) . ")";
    }

    protected function rowToSQL(bool $do_prepared, ?int $idx = null): string
    {
        $placeholders = array();
        $names = $this->fieldset->names();

        foreach ($names as $name) {
            $column = $this->fieldset->getColumn($name);

            if ($do_prepared) {
                $key = $column->getBindingKey();
                if (!is_null($idx)) {
                    $key .= "_" . $idx;
                }
                $placeholders[] = $key;
            } else {
                $val = $column->getValue();
                $placeholders[] = is_array($val) ? $val[$idx] : $val;
            }
        }

        return $this->wrapBrackets($placeholders);
    }

    public function collectSQL(bool $do_prepared): string
    {
        if ($this->fieldset->count() < 1) {
            throw new Exception("Empty fieldset for INSERT");
        }

        $sql = $this->type . " " . $this->from . " ";
        $sql .= $this->wrapBrackets($this->fieldset->names());
        $sql .= " VALUES ";

        $values = $this->fieldset->values();
        $multi_count = 0;
        foreach ($values as $v) {
            if (is_array($v)) { $multi_count = count($v); break; }
        }

        if ($multi_count > 0) {
            $rows = array();
            for ($i = 0; $i < $multi_count; $i++) {
                $rows[] = $this->rowToSQL($do_prepared, $i);
            }
            $sql .= implode(",", $rows);
        } else {
            $sql .= $this->rowToSQL($do_prepared);
        }

        return $sql;
    }

    public function getSQL(): string
    {
        return $this->collectSQL(false);
    }

    public function getPreparedSQL(): string
    {
        return $this->collectSQL(true);
    }

    public function getBindings(): array
    {
        $values = $this->fieldset->values();

        // 1. Бърза проверка за multi-insert
        $multi_count = 0;
        foreach ($values as $v) {
            if (is_array($v)) { $multi_count = count($v); break; }
        }

        // 2. Генериране на биндингите
        if ($multi_count > 0) {
            $bindings = array();
            $names = $this->fieldset->names();
            for ($i = 0; $i < $multi_count; $i++) {
                foreach ($names as $name) {
                    $column = $this->fieldset->getColumn($name);
                    $fullKey = $column->getBindingKey() . "_" . $i;
                    $bindings[$fullKey] = $values[$name][$i] ?? null;
                }
            }
            return $bindings;
        }

        // Твоят вариант: Елегантна изолация за единичен ред
        return parent::getBindings();
    }
}