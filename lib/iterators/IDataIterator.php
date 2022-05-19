<?php
include_once("sql/SQLSelect.php");

interface IDataIterator
{
    public function exec(): int;

    public function next();

    /**
     * @return string The primary key of this data iterator
     */
    public function key(): string;

    /**
     * Data source name (ie table name for DBTableBean)
     * @return string
     */
    public function name(): string;

    public function count(): int;

    public function bean(): ?DBTableBean;

    /**
     * Check if the iterator is already executed
     * @return bool
     */
    public function isActive() : bool;

}

?>