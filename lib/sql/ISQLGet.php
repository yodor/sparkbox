<?php

interface ISQLGet {
    public function getSQL() : string;
    public function getPreparedSQL() : string;
    public function collectSQL(bool $do_prepared) : string;
}