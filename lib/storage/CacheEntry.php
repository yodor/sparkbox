<?php

class CacheEntry
{
    const KEY_DATA = "data";
    const KEY_SIZE = "size";

    protected string $fileName = "";

    /**
     * @var string
     */
    protected string $className = "";

    /**
     * @var int
     */
    protected int $id = -1;

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

        $this->fileName = $cache_folder . DIRECTORY_SEPARATOR . basename($name);

        //debug("Using filename: $this->fileName");
    }

    /**
     * @return bool True if this cache entry file exists
     */
    public function exists(): bool
    {
        return file_exists($this->fileName);
    }

    /**
     * @return string Absolute file name of this cache entry
     */
    public function fileName(): string
    {
        return $this->fileName;
    }

    /**
     * Return contents of the file inside array
     * CacheFile::KEY_DATA holds the contents of the file
     * CacheFile::KEY_SIZE holds the size of the file
     * @return array
     * @throws Exception
     */
    public function load(): array
    {
        if (!$this->exists()) throw new Exception("Cache file does not exist");

        $ret = array();

        $ret[CacheEntry::KEY_DATA] = "";
        $ret[CacheEntry::KEY_SIZE] = -1;

        $handle = fopen($this->fileName, 'r');
        flock($handle, LOCK_SH);
        $ret[CacheEntry::KEY_DATA] = fread($handle, filesize($this->fileName));
        flock($handle, LOCK_UN);
        fclose($handle);

        $ret[CacheEntry::KEY_SIZE] = filesize($this->fileName);

        return $ret;
    }

    protected function getCacheFolder(): string
    {
        return CACHE_PATH . DIRECTORY_SEPARATOR . $this->className . DIRECTORY_SEPARATOR . $this->id;
    }

    public function store($data)
    {
        file_put_contents($this->fileName, $data, LOCK_EX);
        debug("Stored " . filesize($this->fileName) . " bytes");
    }
}
