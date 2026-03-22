<?php

interface IAliasedColumn
{
    public function setAlias(string $alias) : void;
    public function getAlias() : string;
}