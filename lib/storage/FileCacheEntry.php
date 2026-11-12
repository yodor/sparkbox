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
     * Written atomically - see writeAtomic()
     * @param string $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public function store(string $data, int $lastModified = 0): void
    {
        $this->writeAtomic($data, $lastModified);
    }

    /**
     * Store data from this buffer and set last modified time (if non-zero)
     * Written atomically - see writeAtomic()
     * @param DataBuffer $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public function storeBuffer(DataBuffer $data, int $lastModified = 0): void
    {
        $this->writeAtomic($data->data(), $lastModified);
    }

    /**
     * Write to a temporary file in the same folder, set its mtime, then rename() it over the entry.
     * rename() on the same filesystem is atomic: readers see either the complete old file or the
     * complete new one - never a truncated or half written file. Concurrent writers each use their
     * own temporary file and the last rename wins.
     * @param string $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    protected function writeAtomic(string $data, int $lastModified): void
    {
        $target = $this->file->getAbsoluteFilename();
        $temp = $target . "." . bin2hex(random_bytes(6)) . ".tmp";

        $written = @file_put_contents($temp, $data);
        if ($written !== strlen($data)) {
            @unlink($temp);
            throw new Exception("Unable to write cache file: " . (error_get_last()["message"] ?? $temp));
        }

        if ($lastModified > 0) {
            touch($temp, $lastModified);
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new Exception("Unable to replace cache file: " . (error_get_last()["message"] ?? $target));
        }

        clearstatcache(true, $target);
        Debug::ErrorLog("Stored " . $written . " bytes" . ($lastModified > 0 ? " - last-modified: $lastModified" : ""));
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