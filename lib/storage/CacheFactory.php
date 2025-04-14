<?php
include_once("storage/CacheEntry.php");
include_once("storage/FileCacheEntry.php");
include_once("storage/DBCacheEntry.php");
include_once("beans/SparkCacheBean.php");

class CacheFactory
{
    const string BACKEND_DATABASE = "database";
    const string BACKEND_FILESYSTEM = "filesystem";
    /**
     * @param string $cacheName - Cache name identifier (filename)
     * @param string $className - Bean class name
     * @param int $id - Bean primary key ID
     * @return CacheEntry
     * @throws Exception
     */
    public static function BeanCacheEntry(string $cacheName, string $className, int $id) : CacheEntry
    {

        if (empty($cacheName)) {
            throw new Exception("Empty cache name identifier");
        }

        if (empty($className)) {
            throw new Exception("Empty class name identifier");
        }

        if ($id < 1) {
            throw new Exception("Incorrect ID");
        }

        if (strcasecmp(BEAN_CACHE_BACKEND, CacheFactory::BACKEND_DATABASE)==0) {

            return new DBCacheEntry($cacheName,  $className,  $id);

        }
        else if (strcasecmp(BEAN_CACHE_BACKEND, CacheFactory::BACKEND_FILESYSTEM)==0) {

            $cache_folder = CACHE_PATH . DIRECTORY_SEPARATOR . $className . DIRECTORY_SEPARATOR . $id;
            if (!file_exists($cache_folder)) {
                debug("Creating cache folder: $cache_folder");
                @mkdir($cache_folder, 0777, TRUE);
                if (!file_exists($cache_folder)) throw new Exception("Unable to create cache folder: $cache_folder");
            }

            return new FileCacheEntry(new SparkFile($cache_folder . DIRECTORY_SEPARATOR . $cacheName));

        }
        else {
            throw new Exception("Incorrect backend type");
        }

    }

    public static function PageCacheEntry(string $cacheName) : CacheEntry
    {
        if (empty($cacheName)) {
            throw new Exception("Empty cache name identifier");
        }

        if (strcasecmp(PAGE_CACHE_BACKEND, CacheFactory::BACKEND_DATABASE)==0) {
            return new DBCacheEntry($cacheName,  "PageCache",  0);
        }
        else if (strcasecmp(PAGE_CACHE_BACKEND, CacheFactory::BACKEND_FILESYSTEM)==0) {

            $cache_folder = CACHE_PATH . DIRECTORY_SEPARATOR . "PageCache";
            if (!file_exists($cache_folder)) {
                debug("Creating cache folder: $cache_folder");
                @mkdir($cache_folder, 0777, TRUE);
                if (!file_exists($cache_folder)) throw new Exception("Unable to create cache folder: $cache_folder");
            }
            return new FileCacheEntry(new SparkFile($cache_folder . DIRECTORY_SEPARATOR . $cacheName));

        }
        else {
            throw new Exception("Incorrect backend type");
        }
    }

    public static function CleanupPageCache() : void
    {
        $cleanup_file = CACHE_PATH . DIRECTORY_SEPARATOR . "PageCache.cleanup";
        if (!file_exists($cleanup_file)) touch($cleanup_file);
        $cleanup_time = filemtime($cleanup_file);
        $delta = time() - $cleanup_time;
        //default 24 hours - 86400 seconds
        //debug("Cleanup PageCache delta: $delta");

        if ($delta < PAGE_CACHE_CLEANUP_DELTA) {
            debug("Cleanup PageCache remaining time: ". PAGE_CACHE_CLEANUP_DELTA - $delta);
            return;
        }
        //
        $timestamp = time();

        debug("Doing PageCache cleanup - now: $timestamp - TTL: ".PAGE_CACHE_TTL);

        if (strcasecmp(PAGE_CACHE_BACKEND, CacheFactory::BACKEND_DATABASE)==0) {

            $bean = new SparkCacheBean();
            $delete = new SQLDelete($bean->select());
            $delete->where()->add("className", "'PageCache'");
            $delete->where()->addExpression("(($timestamp - lastModified) > ".PAGE_CACHE_TTL.")", " AND ");
            $db = $bean->getDB();
            try {
                $db->transaction();
                $db->query($delete->getSQL());
                $numEntries = $db->affectedRows();
                $db->commit();
                touch($cleanup_file);
                debug("DB PageCache cleanup complete. Affected rows: " . $numEntries);
            }
            catch (Exception $e) {
                $db->rollback();
                debug("Unable to cleanup PageCache: " . $e->getMessage());
            }

        }
        else if (strcasecmp(PAGE_CACHE_BACKEND, CacheFactory::BACKEND_FILESYSTEM)==0) {

            $cache_folder = CACHE_PATH . DIRECTORY_SEPARATOR . "PageCache";

            $check_ttl = function ($item) use ($timestamp) {

                $filestamp = filemtime($item);
                $fileTTL = ($timestamp - $filestamp);
                if ($fileTTL > PAGE_CACHE_TTL) {
                    debug("Removing stale cache entry: " . $item);
                    unlink($item);
                }
            };
            array_map($check_ttl, glob("$cache_folder/*", GLOB_NOSORT | GLOB_NOESCAPE));
            touch($cleanup_file);

        }
    }
}

?>