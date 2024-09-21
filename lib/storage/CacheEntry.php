<?php
include_once("storage/SparkFile.php");

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


    private function __construct(SparkFile $file)
    {
        $this->file = $file;

        debug("Using Cache Folder: ".basename($this->file->getPath()));
        debug("Using Filename: ".$this->file->getFilename());
        debug("Using Path: ".$this->file->getPath());
    }

    /**
     * @throws Exception
     */
    public static function BeanCacheEntry(string $name, string $className, int $id) : CacheEntry
    {

        if (empty($name)) {
            throw new Exception("Empty name");
        }

        if (empty($className)) {
            throw new Exception("Empty className");
        }

        if ($id < 1) {
            throw new Exception("Incorrect ID");
        }

        $cache_folder = CACHE_PATH . DIRECTORY_SEPARATOR . $className . DIRECTORY_SEPARATOR . $id;
        if (!file_exists($cache_folder)) {
            debug("Creating cache folder: $cache_folder");
            @mkdir($cache_folder, 0777, TRUE);
            if (!file_exists($cache_folder)) throw new Exception("Unable to create cache folder: $cache_folder");
        }

        return new CacheEntry(new SparkFile($cache_folder . DIRECTORY_SEPARATOR . $name));

    }

    public static function PageCacheEntry(string $name)
    {
        $cache_folder = CACHE_PATH . DIRECTORY_SEPARATOR . "PageCache";
        if (!file_exists($cache_folder)) {
            debug("Creating cache folder: $cache_folder");
            @mkdir($cache_folder, 0777, TRUE);
            if (!file_exists($cache_folder)) throw new Exception("Unable to create cache folder: $cache_folder");
        }

        return new CacheEntry(new SparkFile($cache_folder . DIRECTORY_SEPARATOR . $name));
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
     * Store data from this buffer and set last modified time (if non-sero))
     * @param DataBuffer $data
     * @param int $lastModified
     * @return void
     * @throws Exception
     */
    public function storeBuffer(DataBuffer $data, int $lastModified=0)
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

    /**
     * @return void
     */
    public static function CleanupPageCache() : void
    {
        $cleanup_file = CACHE_PATH . DIRECTORY_SEPARATOR . "PageCache.cleanup";
        if (!file_exists($cleanup_file)) touch($cleanup_file);
        $cleanup_time = filemtime($cleanup_file);
        $delta = time() - $cleanup_time;
        //default 24 hours - 86400 seconds
        if ($delta < PAGE_CACHE_CLEANUP_DELTA) {
            debug("Cleanup PageCache remaining time: ". PAGE_CACHE_CLEANUP_DELTA - $delta);
            return;
        }
        //
        debug("Doing PageCache cleanup ...");

        $cache_folder = CACHE_PATH . DIRECTORY_SEPARATOR . "PageCache";

        $timestamp = time();
        $check_ttl = function($item) use($timestamp) {

            $filestamp = filemtime($item);
            $fileTTL = ($timestamp - $filestamp);
            if ( $fileTTL > PAGE_CACHE_TTL ) {
                debug("Removing stale cache entry: ".$item);
                unlink($item);
            }
        };
        array_map( $check_ttl, glob( "$cache_folder/*", GLOB_NOSORT | GLOB_NOESCAPE ) );
        touch($cleanup_file);
    }
}
