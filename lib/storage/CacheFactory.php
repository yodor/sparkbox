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

        if (strcasecmp(Spark::Get(Config::BEAN_CACHE_BACKEND), CacheFactory::BACKEND_DATABASE)===0) {

            return new DBCacheEntry($cacheName,  $className,  $id);

        }
        else if (strcasecmp(Spark::Get(Config::BEAN_CACHE_BACKEND), CacheFactory::BACKEND_FILESYSTEM)===0) {

            $cache_folder = Spark::Get(Config::CACHE_PATH) . DIRECTORY_SEPARATOR . $className . DIRECTORY_SEPARATOR . $id;
            if (!file_exists($cache_folder)) {
                Debug::ErrorLog("Creating cache folder: $cache_folder");
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

        if (strcasecmp(Spark::Get(Config::PAGE_CACHE_BACKEND), CacheFactory::BACKEND_DATABASE)===0) {
            return new DBCacheEntry($cacheName,  "PageCache",  0);
        }
        else if (strcasecmp(Spark::Get(Config::PAGE_CACHE_BACKEND), CacheFactory::BACKEND_FILESYSTEM)===0) {

            $cache_folder = Spark::Get(Config::CACHE_PATH) . DIRECTORY_SEPARATOR . "PageCache";
            if (!file_exists($cache_folder)) {
                Debug::ErrorLog("Creating cache folder: $cache_folder");
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
        $cleanup_file = Spark::Get(Config::CACHE_PATH) . DIRECTORY_SEPARATOR . "PageCache.cleanup";
        if (!file_exists($cleanup_file)) touch($cleanup_file);
        $cleanup_time = filemtime($cleanup_file);
        $delta = time() - $cleanup_time;
        //default 1 hour - 3600 seconds
        //Debug::ErrorLog("Cleanup PageCache delta: $delta");

        if ($delta < Spark::GetInteger(Config::PAGE_CACHE_CLEANUP_DELTA)) {
            Debug::ErrorLog("Remaining seconds until PageCache cleanup: ". Spark::GetInteger(Config::PAGE_CACHE_CLEANUP_DELTA) - $delta);
            return;
        }
        //
        $timestamp = time();

        Debug::ErrorLog("Doing PageCache cleanup - now: $timestamp - TTL: ".Spark::GetInteger(Config::PAGE_CACHE_TTL));

        if (strcasecmp(Spark::Get(Config::PAGE_CACHE_BACKEND), CacheFactory::BACKEND_DATABASE)==0) {

            $bean = new SparkCacheBean();
            $delete = new SQLDelete($bean->select());
            $delete->where()->add("className", "'PageCache'");
            $delete->where()->addExpression("(($timestamp - lastModified) > ".Spark::GetInteger(Config::PAGE_CACHE_TTL).")", " AND ");
            $db = $bean->getDB();
            try {
                $db->transaction();
                $db->query($delete->getSQL());
                $numEntries = $db->affectedRows();
                $db->commit();
                touch($cleanup_file);
                Debug::ErrorLog("DB PageCache cleanup complete. Affected rows: " . $numEntries);
            }
            catch (Exception $e) {
                $db->rollback();
                Debug::ErrorLog("Unable to cleanup PageCache: " . $e->getMessage());
            }

        }
        else if (strcasecmp(Spark::Get(Config::PAGE_CACHE_BACKEND), CacheFactory::BACKEND_FILESYSTEM)==0) {

            $cache_folder = Spark::Get(Config::CACHE_PATH) . DIRECTORY_SEPARATOR . "PageCache";

            $handle = fopen($cleanup_file, 'a');
            if (!$handle) {
                Debug::ErrorLog("Unable to open cleanup file: " . $cleanup_file);
                return;
            }
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                // Perform task
                $check_ttl = function ($item) use ($timestamp) {
                    $filestamp = filemtime($item);
                    $fileTTL = ($timestamp - $filestamp);
                    if ($fileTTL >= Spark::GetInteger(Config::PAGE_CACHE_TTL)) {
                        Debug::ErrorLog("Removing stale cache entry: " . $item);
                        unlink($item);
                    }
                };
                array_map($check_ttl, glob("$cache_folder/*", GLOB_NOSORT | GLOB_NOESCAPE));
                touch($cleanup_file);
                flock($handle, LOCK_UN);
            } else {
                //echo "File is locked. Skipping.\n";
            }
            fclose($handle);
        } //CacheFactory::BACKEND_FILESYSTEM
    }

    /**
     * Render component from cache
     * Return true if component contents are found and output
     * Return false if PAGE_CACHE_ENABLED is false, component isCacheable() is false
     * or component getCacheName() is empty string
     * Return new instance of CacheEntry if the entry is empty or the entry is expired
     * @param Component $cmp
     * @return CacheEntry|bool
     * @throws Exception
     */
    public static function CacheEntryOutput(Component $cmp) : CacheEntry | bool
    {

        if (!Spark::GetBoolean(Config::PAGE_CACHE_ENABLED)) return false;
        if (!$cmp->isCacheable()) return false;

        $cacheName = $cmp->getCacheName();
        if (!$cacheName) return false;

        $entryName = get_class($cmp) . "-" . Spark::Hash($cacheName);

        $cacheEntry = CacheFactory::PageCacheEntry($entryName);

        if ($cacheEntry->haveData()) {

            $entryStamp = $cacheEntry->lastModified();
            $timeStamp = time();
            $entryAge = ($timeStamp - $entryStamp);
            $remainingTTL = Spark::GetInteger(Config::PAGE_CACHE_TTL) - $entryAge;

            Debug::ErrorLog("CacheEntry exists - lastModified: " . $entryStamp . " | Remaining TTL: " . $remainingTTL);

            if ($remainingTTL > 0) {
                //output cached data
                echo "<!-- PageCache: $entryName -->";
                $cacheEntry->output();
                return true;
            }
        }

        return $cacheEntry;
    }
}