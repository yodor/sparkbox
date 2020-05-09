<?php

class CacheFile
{
    const KEY_DATA = "data";
    const KEY_SIZE = "size";

    protected $fileExists = FALSE;

    protected $fileName = "";

    public function __construct(string $etag, string $className, int $id)
    {
        $this->etag = $etag;
        $this->className = $className;
        $this->id = $id;

        if (strlen($this->etag) < 1) {
            throw new Exception("Empty ETag");
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
            if (!@mkdir($cache_folder, 0777, TRUE)) throw new Exception("Unable to create cache folder: $cache_folder");
        }

        $this->fileName = $cache_folder . DIRECTORY_SEPARATOR . $this->getCacheFile();

        debug("Using filename: $this->fileName");
    }

    public function exists(): bool
    {
        return file_exists($this->fileName);
    }

    public function fileName(): string
    {
        return $this->fileName;
    }

    public function load(): array
    {
        if (!$this->exists()) throw new Exception("Cache file does not exist");

        $ret = array();

        $ret[CacheFile::KEY_DATA] = "";
        $ret[CacheFile::KEY_SIZE] = -1;

        $handle = fopen($this->fileName, 'r');
        flock($handle, LOCK_SH);
        $ret[CacheFile::KEY_DATA] = fread($handle, filesize($this->fileName));
        flock($handle, LOCK_UN);
        fclose($handle);

        $ret[CacheFile::KEY_SIZE] = filesize($this->fileName);

        return $ret;
    }

    protected function getCacheFolder(): string
    {
        return CACHE_PATH . DIRECTORY_SEPARATOR . $this->className . DIRECTORY_SEPARATOR . $this->id;
    }

    protected function getCacheFile(): string
    {
        return $this->etag . ".bin";
    }

    public function store($data)
    {
        file_put_contents($this->fileName, $data, LOCK_EX);
        debug("Stored " . filesize($this->fileName) . " bytes");
    }
}