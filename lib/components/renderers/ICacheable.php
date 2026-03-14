<?php

interface ICacheable extends ICacheIdentifier
{
    public function setCacheable(bool $mode) : void;
    public function isCacheable() : bool;

    public function getCacheName() : string;
}