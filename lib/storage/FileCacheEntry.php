<?php
include_once("storage/CacheEntry.php");

class FileCacheEntry extends CacheEntry
{
    /**
     * Cache file used to back this cache entry contents
     * @var ?SparkFile
     */
    protected ?SparkFile $file = null;

    public function __construct(SparkFile $file)
    {
        parent::__construct();

        $this->file = $file;

        debug("Using Cache Folder: ".basename($this->file->getPath()));
        debug("Using Filename: ".$this->file->getFilename());
        debug("Using Path: ".$this->file->getPath());
    }

    //replaced getFile()->exists();
    public function haveData() : bool
    {
        return $this->file->exists();
    }

    public function getFile() : SparkFile
    {
        return $this->file;
    }

    /**
     * Output file using shared read lock
     * @return void
     * @throws Exception
     */
    public function output() : void
    {
        $this->file->open('r');
        $this->file->lock(LOCK_SH);
        $this->file->passthru();
        $this->file->lock(LOCK_UN);
        $this->file->close();
    }

    /**
     * Store data to this cache entry file and set the last modified time (if non-zero)
     * @param string $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public function store(string $data, int $lastModified=0) : void
    {
        $this->file->open('w');
        $this->file->lock(LOCK_EX);
        $this->file->write($data);
        $this->file->lock(LOCK_UN);
        $this->file->close();
        debug("Stored " . $this->file->length() . " bytes");
        if ($lastModified>0) {
            $this->file->setLastModified($lastModified);
            debug("File last-modified set to: " . $lastModified);
        }
    }

    /**
     * Store data from this buffer and set last modified time (if non-sero))
     * @param DataBuffer $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public function storeBuffer(DataBuffer $data, int $lastModified=0) : void
    {
        $this->file->open('w');
        $this->file->lock(LOCK_EX);
        $this->file->write($data->data());
        $this->file->lock(LOCK_UN);
        $this->file->close();
        debug("Stored " . $this->file->length() . " bytes");
        if ($lastModified>0) {
            $this->file->setLastModified($lastModified);
            debug("File last-modified set to: " . $lastModified);
        }
    }
    /**
     * @return int Unix timestamp of this cache entry file
     * @throws Exception
     */
    public function lastModified() : int
    {
        return $this->file->lastModified();
    }
}