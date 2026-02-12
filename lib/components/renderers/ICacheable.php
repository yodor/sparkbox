<?php

interface ICacheable
{
    public function setCacheable(bool $mode) : void;
    public function isCacheable() : bool;

    public function getCacheName() : string;
}