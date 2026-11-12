<?php

/**
 * Per row change marker for storage data (images / files served by storage.php).
 *
 * Marker file: CACHE_PATH/<BeanClass>/<id>.v - its mtime is the time of the last write of that row
 * done through the bean (DBTableBean::manageCache). It sits next to the bean cache folder
 * CACHE_PATH/<BeanClass>/<id>/ so deleting that folder keeps the marker.
 *
 * The marker is the single source of last-modified time for storage responses (Last-Modified,
 * ETag) and for the version parameter of storage URLs. It does not depend on date columns of the
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
     * @param string $className
     * @param int $id
     * @return string
     * @throws Exception
     */
    protected static function MarkerFile(string $className, int $id) : string
    {
        if (!Spark::ValidClassName($className)) throw new Exception("Incorrect class name");
        if ($id < 1) throw new Exception("Incorrect ID");
        return Spark::Get(Config::CACHE_PATH) . DIRECTORY_SEPARATOR . $className . DIRECTORY_SEPARATOR . $id . StorageVersion::SUFFIX;
    }

    /**
     * Create the class folder if needed
     * @param string $markerFile
     * @return bool
     */
    protected static function EnsureFolder(string $markerFile) : bool
    {
        $folder = dirname($markerFile);
        if (is_dir($folder)) return true;
        @mkdir($folder, 0777, true);
        return is_dir($folder);
    }

    /**
     * Mark row '$id' of '$className' as changed now. Called on every write through the bean.
     * Never throws - a failed marker update must not fail the DB write.
     * @param string $className
     * @param int $id
     * @return void
     */
    public static function Touch(string $className, int $id) : void
    {
        try {
            $file = StorageVersion::MarkerFile($className, $id);
            if (!StorageVersion::EnsureFolder($file)) {
                Debug::ErrorLog("StorageVersion: unable to create folder for: $file");
                return;
            }
            //time() may equal the current mtime when two writes happen within one second -
            //move forward by one second so every write produces a new version
            $now = time();
            $current = @filemtime($file);
            if ($current !== false && $current >= $now) {
                $now = $current + 1;
            }
            if (!@touch($file, $now)) {
                Debug::ErrorLog("StorageVersion: unable to touch: $file");
            }
            clearstatcache(true, $file);
        }
        catch (Exception $e) {
            Debug::ErrorLog("StorageVersion: " . $e->getMessage());
        }
    }

    /**
     * Unix time of the last change of row '$id' of '$className'.
     * Creates the marker with the current time if it does not exist.
     * @param string $className
     * @param int $id
     * @return int Timestamp or 0 if the marker can not be read nor created
     */
    public static function Get(string $className, int $id) : int
    {
        try {
            $file = StorageVersion::MarkerFile($className, $id);
        }
        catch (Exception $e) {
            return 0;
        }

        $mtime = @filemtime($file);
        if ($mtime !== false) return $mtime;

        if (!StorageVersion::EnsureFolder($file)) return 0;

        //exclusive create - if another request created it first use its time
        $handle = @fopen($file, "x");
        if ($handle !== false) {
            fclose($handle);
            Debug::ErrorLog("StorageVersion: created marker: $file");
        }
        clearstatcache(true, $file);

        $mtime = @filemtime($file);
        return ($mtime === false) ? 0 : $mtime;
    }

    /**
     * Version token for storage URLs: base36 of the row marker time, for images also the
     * watermark file time (changing the watermark file changes all image URLs).
     * @param string $className
     * @param int $id
     * @param bool $isImage
     * @return string Empty string if no version is available
     */
    public static function Token(string $className, int $id, bool $isImage) : string
    {
        $version = StorageVersion::Get($className, $id);
        if ($version < 1) return "";

        $token = base_convert((string)$version, 10, 36);

        if ($isImage) {
            $watermark = StorageVersion::WatermarkTime();
            if ($watermark > 0) $token .= "." . base_convert((string)$watermark, 10, 36);
        }
        return $token;
    }

    /**
     * mtime of the configured watermark file or 0 - cached per request
     * @return int
     */
    protected static function WatermarkTime() : int
    {
        static $time = null;
        if ($time !== null) return $time;

        $time = 0;
        if (Spark::GetBoolean(Config::IMAGE_SCALER_WATERMARK_ENABLED)) {
            $filename = Spark::Get(Config::IMAGE_SCALER_WATERMARK_FILENAME);
            if ($filename) {
                $mtime = @filemtime(Spark::Get(Config::APP_PATH) . DIRECTORY_SEPARATOR . $filename);
                if ($mtime !== false) $time = $mtime;
            }
        }
        return $time;
    }
}
