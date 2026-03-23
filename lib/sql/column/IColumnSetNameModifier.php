<?php
interface IColumnSetNameModifier
{
    /**
     * Set prefix to all columns that are not expressions
     *
     * @param string $prefix
     * @return void
     */
    public function setPrefix(string $prefix) : void;

    /**
     * Remove the prefix from all columns that are not expressions
     *
     * @return void
     */
    public function clearPrefix() : void;

    /**
     * Check if column is present.
     *
     * Require the prefixed name.
     *
     * @param string $prefixedName Column name including the prefix
     * @return bool
     */
    public function isSet(string $prefixedName): bool;

    /**
     * Remove column name from the fieldset.
     *
     * Require the prefixed name if column was prefixed before
     * ie p.status
     *
     * @param string $prefixedName Column name including the prefix
     */
    public function unset(string $prefixedName) : void;

    /**
     * Remove all columns
     *
     * @return void
     */
    public function reset() : void;

    /**
     * Columns count
     * @return int
     */
    public function count(): int;

    /**
     * Return all columns names (prefixed)
     * @return array
     */
    public function names() : array;


}