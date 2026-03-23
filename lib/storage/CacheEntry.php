<?php

abstract class CacheEntry
{

    protected string $entryName = "";

    protected function __construct(string $entryName)
    {
        if (strlen(trim($entryName)) < 1) throw new Exception("Cache entry name must not be empty");
        $this->entryName = $entryName;
    }

    public function getEntryName(): string
    {
        return $this->entryName;
    }

    public abstract function haveData() : bool;

    /**
     * Output this cache entry contents
     * @return void
     * @throws Exception
     */
    public abstract function output() : void;

    /**
     * Store $data to this cache entry and set the last modified time (if non-zero)
     * @param string $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public abstract function store(string $data, int $lastModified=0) : void;

    /**
     * Store DataBuffer contents to this cache entry and set last modified time (if non-zero)
     * @param DataBuffer $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public abstract function storeBuffer(DataBuffer $data, int $lastModified=0) : void;

    /**
     * @return int Unix timestamp of this cache entry last modified
     * @throws Exception
     */
    public abstract function lastModified() : int;

    public abstract function remove() : void;
}