<?php
include_once("storage/SparkHTTPResponse.php");
include_once("storage/CacheEntry.php");
include_once("storage/FileStorageObject.php");
include_once("auth/Authenticator.php");
include_once("storage/DataBuffer.php");

abstract class BeanDataResponse extends SparkHTTPResponse
{

    const FIELD_PHOTO = "photo";
    const FIELD_DATA = "data";

    protected string $className = "";
    protected int $id = -1;
    protected string $field = "";

    /**
     * @var StorageObject
     */
    protected StorageObject $object;

    protected ?CacheEntry $cacheEntry;

    /**
     * @var DBTableBean
     */
    protected DBTableBean $bean;

    protected bool $skip_cache = FALSE;

    /**
     * Timestamp
     * @var int
     */
    protected int $last_modified = -1;

    /**
     * This flag is set when request URI contains specific field name to return from the result row.
     * Custom key name from the result row is allowed only if contents are of type StorageObject
     * To limit arbitrary data access
     * @var bool
     */
    protected bool $field_requested = FALSE;

    protected bool $require_watermark = FALSE;

    /**
     * @param int $id
     * @param string $className
     * @throws Exception
     */
    public function __construct(int $id, string $className)
    {
        parent::__construct();

        $this->id = $id;
        $this->className = $className;

        $globals = SparkGlobals::Instance();
        $globals->includeBeanClass($className);

        $this->bean = new $this->className();

        if (isset($_GET["field"])) {
            $this->field = $_GET["field"];
            $this->field_requested = TRUE;

            debug("Requested bean field: $this->field");
        }

        $this->cacheEntry = NULL;

        if (STORAGE_CACHE_ENABLED && !$this->skip_cache) {
            $this->cacheEntry = CacheEntry::BeanCacheEntry($this->cacheName(), $this->className, $this->id);
        }


    }

    /**
     * @throws Exception
     */
    protected function authorizeAccess(): void
    {
        debug("authorizeAccess ...");

        if (!$this->bean->haveColumn("auth_context")) {
            debug("No auth_context column defined");
            return;
        }

        //exclude blob type columns
        $beanColumns = $this->bean->columns();
        foreach ($beanColumns as $columnName => $storageType) {
            if (str_contains($storageType, "blob")) {
                unset($beanColumns[$columnName]);
            }
        }

        $dataColumns = array_keys($beanColumns);
        $data = $this->bean->getByID($this->id, ...$dataColumns);

        $auth_context = (string)$data["auth_context"];

        if (strlen($auth_context) < 1) {
            debug("auth_context not set for this id");
            return;
        }

        debug("Protected resource requested. Using auth_context: $auth_context");

        Session::Start();
        Authenticator::AuthorizeResource($auth_context, $data, TRUE);
        Session::Close();
    }

    /**
     * Create and set the StorageObject with data from DB
     * @return void
     * @throws Exception
     */
    protected function loadBean() : void
    {

        debug("Loading ID: " . $this->id . " from " . $this->className);

        $result = $this->bean->getByID($this->id);
        debug("Data keys loaded: ", array_keys($result));

        if (!isset($result[$this->field])) {
            debug("Required field name not found");
            throw new Exception("Field name not found");
        }

        if (strlen($result[$this->field])<1) {
            throw new Exception("Empty data");
        }

        $object = @unserialize($result[$this->field]);

        if ($object instanceof StorageObject) {
            debug("Unpacked: " . get_class($object));
            $object->setDataKey($this->field);
            $this->object = $object;
        }
        else {

            debug("Field[$this->field] does not contain StorageObject");

            //Limit arbitrary data access from the result row
            if ($this->field_requested) {
                throw new Exception("Named field access restricted to StorageObject types only");
            }

            //Create storage object using the default data key
            $this->object = StorageObject::CreateFrom($result, $this->field);

        }

        if (isset($result["watermark_enabled"])) {
            $this->require_watermark = (bool)$result["watermark_enabled"];
        }
    }


    /**
     * Prepare and set 'ETag', 'Last-Modified' and 'Expires' HTML header fields.
     * If ETag is already set do nothing.
     * @return void
     * @throws Exception
     */
    protected function fillCacheHeaders() : void
    {
        $last_modified = $this->getLastModified();

        //set last modified from bean
        $modified = gmdate(SparkHTTPResponse::DATE_FORMAT, $last_modified);
        debug("Last-Modified: $modified");

        //keep one year ahead from request time
        $expires = gmdate(SparkHTTPResponse::DATE_FORMAT, strtotime("+1 year"));
        debug("Expires: $expires");

        $this->setHeader("ETag", $this->ETag());

        $this->setHeader("Last-Modified", $modified);

    }


    /**
     * Get last modified from cache file or from bean
     * Reuse this value further during this request call
     * @return int
     * @throws Exception
     */
    protected function getLastModified() : int
    {
        if ($this->last_modified!=-1) return $this->last_modified;

        if ($this->cacheEntry && $this->cacheEntry->getFile()->exists()) {
            debug("Reading last-modified from filesystem");
            $last_modified = $this->cacheEntry->lastModified();
        }
        else {
            debug("Reading last-modified from bean");
            $last_modified = $this->getBeanLastModified();
        }

        $this->last_modified = $last_modified;

        return $this->last_modified;
    }

    /**
     * Return the last modified time for the requested row 'id'
     * Fetches columns date_upload or date_updated of this bean to construct the last modified time
     * If these columns are not present in this bean, use time() as result
     * @return int
     * @throws Exception
     */
    protected function getBeanLastModified() : int
    {

        $last_modified = time();

        //default value
        if ($this->id==-1) {
            debug("'Default value'");
            return $last_modified;
        }

        $columns = array("date_upload", "date_updated");
        foreach($columns as $idx=>$name) {
            if (!$this->bean->haveColumn($name)) {
                unset($columns[$idx]);
            }
        }

        if (count($columns)<1) {
            debug("No suitable column found");
            return $last_modified;
        }

        $row = $this->bean->getByID($this->id, ...$columns);

        $found = false;
        foreach ($columns as $name) {
            if (isset($row[$name]) && $row[$name]) {

                $value = strtotime($row[$name]);
                if ($value === FALSE) {
                    continue;
                }

                $last_modified = $value;
                debug("Using value from column [$name]");
                $found = true;
                break;
            }
        }

        if (!$found) {
            debug("No suitable column value found");
        }

        return $last_modified;
    }

    /**
     * Output contents of bean data with key '$this->field'.
     * Using ETag/IF_MODIFIED_SINCE logic - checks the disk cache and return 304
     * Store to filesystem cache for reuse if cache is enabled
     * @return void
     * @throws Exception
     */
    public function send() : void
    {
        debug("Class: " . $this->className . " ID: " . $this->id . " Field: " . $this->field);

        if (!$this->bean->haveColumn($this->field)) {
            throw new Exception("Bean does not support this field");
        }

        //check auth_context field exists for this bean and authorize
        $this->authorizeAccess();

        $lastModified = $this->getLastModified();
        $this->checkCacheLastModifed($lastModified);

        $beanETag = $this->ETag();
        $this->checkCacheETag($beanETag);

        $this->setHeader("Last-Modified", gmdate(SparkHTTPResponse::DATE_FORMAT, $lastModified));
        $this->setHeader("ETag", $beanETag);

        //prefer ETag as disposition filename
        $this->setDispositionFilename($beanETag);

        $cacheName = $this->cacheName();
        debug("Cache name is: ".$cacheName);

        //check if we have the data in cache (skip fetching blob data from DB and image processing if found)
        if ($this->cacheEntry && $this->cacheEntry->getFile()->exists()) {
            debug("Bean data found in cache - sending cache file as a response");
            $this->setHeader("X-Tag", "SparkCache");
            $this->sendFile($this->cacheEntry->getFile());
            exit;
        }

        //fully load the bean data including the blob field
        $this->loadBean();

        //do the magic - ie image resize; also set content length header
        $this->process();

        //store to cache
        if ($this->cacheEntry) {
            debug("Storing cache file for this bean request");
            $this->cacheEntry->storeBuffer($this->object->buffer(), $lastModified);
            debug("Sending cache file as a response");
            $this->sendFile($this->cacheEntry->getFile());
        }
        else {
            //debug case only when cache is disabled
            debug("Using sendData as a response");
            //cache headers
            $this->fillCacheHeaders();
            $this->sendData($this->object->buffer());

        }

        exit;

    }

    /**
     * Prepare default ETag using cacheName() and getLastModified()
     * @return string
     * @throws Exception
     */
    protected function ETag() : string
    {
        return sparkHash($this->cacheName()."-".$this->getLastModified());
    }

    abstract protected function process();
    abstract protected function cacheName() : string;


}
