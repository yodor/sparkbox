<?php
include_once("storage/SparkFile.php");
include_once("storage/CacheFactory.php");
include_once("storage/SparkWatermark.php");

/**
 * Change marker of one row of storage data (images / files served by storage.php).
 *
 * Marker file: CACHE_PATH/<BeanClass>/<id>.v - next to the bean cache folder CACHE_PATH/<BeanClass>/<id>/
 * (see CacheFactory::BeanCacheFolder) so deleting that folder keeps the marker. Its mtime is the time of
 * the last committed write of that row done through the bean (DBTableBean::manageCache).
 *
 * The marker is the single source of last-modified time for storage responses (Last-Modified,
 * ETag) and for the version token of storage URLs. It does not depend on date columns of the
 * table (zero dates, DATETIME without ON UPDATE, missing columns).
 *
 * Rows without a marker get one on first use with the current time - their URL and ETag change
 * once, after that only writes through the bean move it forward.
 * Writes done with raw SQL bypassing the bean are not tracked (same as the bean cache).
 */
class StorageVersion
{
    const string SUFFIX = ".v";

    /**
     * URL parameter name of the version token
     */
    const string KEY = "v";

    /**
     * Marker file
     * @var SparkFile
     */
    protected SparkFile $marker;

    /**
     * Shared by all instances - watermark config does not change during a request
     * @var SparkWatermark|null
     */
    protected static ?SparkWatermark $watermark = null;

    /**
     * @param string $className Bean class name
     * @param int $id Row primary key
     * @throws Exception If the class name or the id is not valid
     */
    public function __construct(string $className, int $id)
    {
        $this->marker = new SparkFile(CacheFactory::BeanCacheFolder($className, $id) . StorageVersion::SUFFIX);
    }

    /**
     * Token format: base36 marker time [ '.' base36 watermark time ]
     * @param string $token
     * @return bool
     */
    public static function ValidToken(string $token) : bool
    {
        return (bool)preg_match('/^[0-9a-z]+(\.[0-9a-z]+)?$/', $token);
    }

    /**
     * Mark the row as changed now. Called after every committed write through the bean.
     * Never throws - a failed marker update must not fail the write.
     * @return void
     */
    public function touch() : void
    {
        try {
            if (!$this->marker->ensurePath()) {
                Debug::ErrorLog("StorageVersion: unable to create folder for: " . $this->marker->getAbsoluteFilename());
                return;
            }
            $this->marker->createExclusive();

            //time() may equal the current mtime when two writes happen within one second -
            //move forward by one second so every write produces a new version
            $now = time();
            $current = $this->marker->lastModified();
            if ($current >= $now) {
                $now = $current + 1;
            }
            $this->marker->setLastModified($now);
            clearstatcache(true, $this->marker->getAbsoluteFilename());
        }
        catch (Exception $e) {
            Debug::ErrorLog("StorageVersion: " . $e->getMessage());
        }
    }

    /**
     * Unix time of the last change of the row. Creates the marker with the current time if it does not exist.
     * @return int Timestamp or 0 if the marker can not be read nor created
     */
    public function time() : int
    {
        try {
            return $this->marker->lastModified();
        }
        catch (Exception $e) {
            //no marker yet
        }

        if (!$this->marker->ensurePath()) return 0;

        //exclusive create - if another request created it first its time is used
        if ($this->marker->createExclusive()) {
            Debug::ErrorLog("StorageVersion: created marker: " . $this->marker->getAbsoluteFilename());
        }

        try {
            return $this->marker->lastModified();
        }
        catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Version token for storage URLs: base36 of the marker time, for images also the
     * watermark file time (changing the watermark file changes all image URLs).
     * @param bool $isImage
     * @return string Empty string if no version is available
     */
    public function token(bool $isImage) : string
    {
        $time = $this->time();
        if ($time < 1) return "";

        $token = base_convert((string)$time, 10, 36);

        if ($isImage) {
            $watermark = StorageVersion::Watermark()->lastModified();
            if ($watermark > 0) $token .= "." . base_convert((string)$watermark, 10, 36);
        }
        return $token;
    }

    /**
     * True if '$token' is the current version token
     * @param string $token
     * @param bool $isImage
     * @return bool
     */
    public function matches(string $token, bool $isImage) : bool
    {
        $current = $this->token($isImage);
        return ($current !== "" && hash_equals($current, $token));
    }

    protected static function Watermark() : SparkWatermark
    {
        if (StorageVersion::$watermark === null) {
            StorageVersion::$watermark = new SparkWatermark();
        }
        return StorageVersion::$watermark;
    }
}
