<?php
include_once ("utils/SparkFile.php");

class CacheEntry
{
    /**
     * Bean class name
     * @var string
     */
    protected string $className = "";

    /**
     * Bean id
     * @var int
     */
    protected int $id = -1;


    /**
     * Cache file used for this entry
     * @var SparkFile
     */
    protected SparkFile $file;


    /**
     * @throws Exception
     */
    public function __construct(string $name, string $className, int $id)
    {

        $this->className = $className;
        $this->id = $id;

        if (strlen($name) < 1) {
            throw new Exception("Empty entry name");
        }

        if (strlen($this->className) < 1) {
            throw new Exception("Empty className");
        }

        if ($this->id < 1) {
            throw new Exception("Incorrect ID");
        }

        if (!defined("CACHE_PATH")) {
            throw new Exception("CACHE_PATH is undefined");
        }
        if (strlen(CACHE_PATH) < 1) {
            throw new Exception("CACHE_PATH is empty");
        }

        $cache_folder = $this->getCacheFolder();
        if (!file_exists($cache_folder)) {
            debug("Creating cache folder: $cache_folder");
            @mkdir($cache_folder, 0777, TRUE);
            if (!file_exists($cache_folder)) throw new Exception("Unable to create cache folder: $cache_folder");
        }

        $this->file = new SparkFile($cache_folder . DIRECTORY_SEPARATOR . basename($name));

        debug("Using Cach Folder: ".$cache_folder);
        debug("Using Filename: ".$this->file->getFilename());
        debug("Using Path: ".$this->file->getPath());

    }

    public function getFile() : SparkFile
    {
        return $this->file;
    }

    /**
     * Get the folder containing this cache entry file
     * @return string
     */
    protected function getCacheFolder(): string
    {
        return CACHE_PATH . DIRECTORY_SEPARATOR . $this->className . DIRECTORY_SEPARATOR . $this->id;
    }

    /**
     * Store data to this cache entry file and set the last modified time (if non-zero)
     * @param string $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public function store(string $data, int $lastModified=0)
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
     * @return int Unix timestamp of this cache entry file
     * @throws Exception
     */
    public function lastModified() : int
    {
        return $this->file->lastModified();
    }
}