<?php
include_once("sql/SQLSelect.php");

interface IDataIterator
{
    public function exec(): void;

    public function next() : ?array;

    /**
     * Accessible primary key name
     * @return string The primary key of this data iterator
     */
    public function key(): string;

    public function count(): int;

    public function bean(): ?DBTableBean;

    /**
     * Accessible table name
     * @return string
     */
    public function table() : string;

    /**
     * Check if the iterator is already executed
     * @return bool
     */
    public function isActive() : bool;

}