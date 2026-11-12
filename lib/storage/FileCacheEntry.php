<?php
include_once("storage/CacheEntry.php");
include_once("storage/SparkFile.php");

class FileCacheEntry extends CacheEntry
{
    /**
     * Cache file used to back this cache entry contents
     * @var ?SparkFile
     */
    protected ?SparkFile $file = null;

    public function __construct(string $cacheFolder, string $entryName)
    {
        parent::__construct($entryName);

        $this->file = new SparkFile($cacheFolder . DIRECTORY_SEPARATOR . $entryName);
        Debug::ErrorLog("EntryName[$entryName] => {$this->file->getAbsoluteFilename()}");
    }

    //replaced getFile()->exists();
    public function haveData(): bool
    {
        Debug::ErrorLog("HaveData for {$this->file->getAbsoluteFilename()} : ".($this->file->exists()?"YES":"NO"));
        return $this->file->exists();
    }

    public function getFile(): SparkFile
    {
        return $this->file;
    }

    /**
     * Output file using shared read lock
     * @return void
     * @throws Exception
     */
    public function output(): void
    {
        $this->file->open('r');
        $this->file->lock(LOCK_SH);
        $this->file->passthru();
        $this->file->lock(LOCK_UN);
        $this->file->close();
    }

    /**
     * Store data to this cache entry file and set the last modified time (if non-zero)
     * Written atomically - see SparkFile::replace()
     * @param string $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public function store(string $data, int $lastModified = 0): void
    {
        $written = $this->file->replace($data, $lastModified);
        Debug::ErrorLog("Stored " . $written . " bytes" . ($lastModified > 0 ? " - last-modified: $lastModified" : ""));
    }

    /**
     * Store data from this buffer and set last modified time (if non-zero)
     * Written atomically - see SparkFile::replace()
     * @param DataBuffer $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public function storeBuffer(DataBuffer $data, int $lastModified = 0): void
    {
        $this->store($data->data(), $lastModified);
    }

    /**
     * Entries are stored with mtime = last-modified of the data they hold.
     * An entry with a different mtime holds an older version (or was removed meanwhile).
     * @param int $lastModified
     * @return bool
     */
    public function isCurrent(int $lastModified): bool
    {
        if (!$this->haveData()) return false;
        try {
            return ($this->file->lastModified() === $lastModified);
        }
        catch (Exception $e) {
            //removed meanwhile
            return false;
        }
    }

    /**
     * @return int Unix timestamp of this cache entry file
     * @throws Exception
     */
    public function lastModified(): int
    {
        return $this->file->lastModified();
    }

    public function remove(): void
    {
        if ($this->file->exists()) {
            $this->file->remove();
        }
    }
}