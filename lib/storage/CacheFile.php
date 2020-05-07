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
            debug("Creating cache folder");
            mkdir($cache_folder, 0777, true);
        }



        $this->fileName = $cache_folder . DIRECTORY_SEPARATOR . $this->getCacheFile();


        debug("Using filename: $this->fileName");
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
        return CACHE_ROOT . DIRECTORY_SEPARATOR . $this->className . DIRECTORY_SEPARATOR . $this->id ;
    }

    protected function getCacheFile() : string
    {
        return $this->etag . ".bin";
    }

    public function store($data)
    {
        $handle = fopen($this->fileName, 'c');
        //acquire an exclusive lock (writer)
        flock($handle, LOCK_EX);
        ftruncate($handle, 0);
        file_put_contents($this->fileName, $data);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        debug("Store complete - size: ".filesize($this->fileName)." filename: {$this->fileName}");
    }
}