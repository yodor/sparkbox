<?php

interface INamedColumn
{
    public function setPrefix(string $prefix) : void;
    public function getPrefix() : string;

    public function getName() : string;
    public function getNamePrefix() : string;
}