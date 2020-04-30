<?php


class CacheFile
{
    const KEY_DATA = "data";
    const KEY_SIZE = "size";

    protected $fileExists = false;

    protected $fileName = "";

    public function __construct(string $etag, string $className, int $id)
    {
        $this->etag = $etag;
        $this->className = $className;
        $this->id = $id;

        if (strlen($this->etag) < 1) {
            throw new Exception("Empty ETag");
        }

        if (strlen($this->className) < 1){
            throw new Exception("Empty className");
        }

        if ($this->id < 1) {
            throw new Exception("Incorrect ID");
        }

        if (!defined("CACHE_ROOT")) {
            throw new Exception("CACHE_ROOT undefined");
        }

        $cache_folder = $this->getCacheFolder();
        if (!file_exists($cache_folder)) {
            $this->debugActiveTime("Creating cache folder");
            mkdir($cache_folder, 0777, true);
        }

        debug("Using cache folder: $cache_folder");

        $this->fileName = $cache_folder . "/" . $this->getCacheFile();
    }

    public function exists() : bool
    {
        return file_exists($this->fileName);
    }

    public function load() : array
    {
        if (!$this->exists()) throw new Exception("Cache file does not exist");

        $ret = array();

        $ret[CacheFile::KEY_DATA] = "";

        $handle = fopen($this->fileName, 'r');
        flock($handle, LOCK_SH);
        $ret[CacheFile::KEY_DATA] = file_get_contents($this->fileName);
        flock($handle, LOCK_UN);
        fclose($handle);

        $ret[CacheFile::KEY_SIZE] = filesize($this->fileName);

        return $ret;
    }

    protected function getCacheFolder() : string
    {
        return CACHE_ROOT . "/" . $this->className . "/" . $this->id . "/";
    }

    protected function getCacheFile() : string
    {
        return $this->etag . ".bin";
    }

    public function store($data)
    {
        $handle = fopen($this->fileName, 'c');
        flock($handle, LOCK_EX);
        ftruncate($handle, 0);
        file_put_contents($this->fileName, $data);
        flock($handle, LOCK_UN);
        fclose($handle);

        debug("Data saved to file: {$this->fileName} - Size: ".filesize($this->fileName));
    }
}