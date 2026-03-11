<?php

interface ISQLGet {

    /**
     * Returns SQL text for this statement
     * @return string
     */
    public function getSQL() : string;

    /**
     * Returns the SQL text for this statement using named bindings suitable for prepare statement
     * @return string
     */
    public function getPreparedSQL() : string;

    /**
     * Return SQL text according to "$do_prepared" flag ie getSQL or getPreparedSQL
     * @param bool $do_prepared
     * @return string
     */
    public function collectSQL(bool $do_prepared) : string;
}