<?php
include_once("storage/SparkHTTPResponse.php");
include_once("storage/CacheFactory.php");
include_once("storage/FileStorageObject.php");
include_once("auth/Authenticator.php");
include_once("storage/DataBuffer.php");

abstract class BeanDataResponse extends SparkHTTPResponse
{

    const string FIELD_PHOTO = "photo";
    const string FIELD_DATA = "data";

    protected string $className = "";
    protected int $id = -1;

    private string $field = "";

    /**
     * @var StorageObject|null
     */
    protected ?StorageObject $object = null;

    /**
     * @var FileCacheEntry|null
     */
    protected ?FileCacheEntry $cacheEntry = null;

    //meta for auth required
    protected ?FileCacheEntry $authEntry = null;

    protected string $authClass = "";

    /**
     * @var DBTableBean|null
     */
    protected ?DBTableBean $bean = null;

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
     * @param string $fieldName
     * @throws Exception
     */
    public function __construct(int $id, string $className, string $fieldName)
    {
        parent::__construct();

        $this->id = $id;
        $this->className = $className;
        $this->field = $fieldName;

        $this->bean = null;

        $this->cacheEntry = NULL;
        $this->authEntry = NULL;

        if (Spark::GetBoolean(Config::STORAGE_CACHE_ENABLED) && !$this->skip_cache) {
            $entryName = $this->cacheName();
            $this->cacheEntry = CacheFactory::BeanCacheEntry($entryName, $this->className, $this->id);
            $this->authEntry = CacheFactory::BeanCacheEntry($fieldName.".auth", $this->className, $this->id);
        }

    }

    private function createBeanInstance() : void
    {
        if (!$this->bean) {
            $bean = SparkLoader::Factory(SparkLoader::PREFIX_BEANS)->instance($this->className, DBTableBean::class);
            if (!($bean instanceof DBTableBean)) throw new Exception("Incorrect object loaded - expecting DBTableBean");
            if (!$bean->haveColumn($this->field)) throw new Exception("Column not found: " . $this->field);
            if (!str_contains(strtolower($bean->columnType($this->field)), "blob")) throw new Exception("Column type incorrect: " . $this->field);
            $this->bean = $bean;
        }
    }

    /**
     * @throws Exception
     */
    protected function authorizeAccess(): void
    {

        if ($this->cacheEntry && $this->cacheEntry->haveData()) {
            if (!$this->authEntry->haveData()) {
                Debug::ErrorLog("Skipping authorize for cached blob - auth file not found");
                return;
            }
        }

        //create the instance here
        $this->createBeanInstance();

        //load bean class
        if (!$this->bean->haveColumn("auth_context")) {
            Debug::ErrorLog("No auth_context column defined");
            return;
        }

        //exclude blob type columns
        $dataColumns = $this->bean->columnNames();
        foreach ($dataColumns as $idx => $name) {
            if (str_contains(strtolower($this->bean->columnType($name)), "blob")) {
                unset($dataColumns[$idx]);
            }
        }

        $data = $this->bean->getByID($this->id, ...$dataColumns);

        $auth_context = (string)$data["auth_context"];
        $this->authClass = $auth_context;

        if (strlen(trim($auth_context)) < 1) {
            Debug::ErrorLog("auth_context not set for this id");
            return;
        }

        Debug::ErrorLog("Protected resource requested. Using auth_context: $auth_context");

        Session::Start();
        Authenticator::AuthorizeResource($auth_context, $data, TRUE);
        Session::Close();

    }

    /**
     *
     * Fully load the blob data and set it to $this->object.
     *
     * @return void
     * @throws Exception
     */
    protected function loadBlob() : void
    {

        Debug::ErrorLog("Loading ID: " . $this->id . " from " . $this->className);

        $this->createBeanInstance();

        //load fully
        $result = $this->bean->getByID($this->id, ...$this->bean->columnNames());
        Debug::ErrorLog("Data keys loaded: ", array_keys($result));

        //just in case
        if (!isset($result[$this->field])) throw new Exception("Result missing column[$this->field]");
        if (strlen($result[$this->field])<1) throw new Exception("No data in column[$this->field]");

        $object = @unserialize($result[$this->field]);

        if ($object instanceof StorageObject) {
            Debug::ErrorLog("Unpacked: " . get_class($object));
            $object->setDataKey($this->field);
            $this->object = $object;
        }
        else {
            throw new Exception("Un-serialize of StorageObject failed");
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
        Debug::ErrorLog("Last-Modified: $modified");

        //keep one year ahead from request time
        $expires = gmdate(SparkHTTPResponse::DATE_FORMAT, strtotime("+1 year"));
        Debug::ErrorLog("Expires: $expires");

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

        if ($this->cacheEntry && $this->cacheEntry->haveData()) {
            Debug::ErrorLog("Reading last-modified from filesystem");
            $last_modified = $this->cacheEntry->lastModified();
        }
        else {
            Debug::ErrorLog("Reading last-modified from bean");
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

        $this->createBeanInstance();

        $last_modified = time();

        //default value
        if ($this->id==-1) {
            Debug::ErrorLog("'Default value'");
            return $last_modified;
        }

        $columns = array("date_upload", "date_updated");
        foreach($columns as $idx=>$name) {
            if (!$this->bean->haveColumn($name)) {
                unset($columns[$idx]);
            }
        }

        if (count($columns)<1) {
            Debug::ErrorLog("No suitable column found");
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
                Debug::ErrorLog("Using value from column [$name]");
                $found = true;
                break;
            }
        }

        if (!$found) {
            Debug::ErrorLog("No suitable column value found");
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
        Debug::ErrorLog("Class: " . $this->className . " ID: " . $this->id);

        //check auth_context field exists for this bean and authorize
        $this->authorizeAccess();

        $lastModified = $this->getLastModified();
        $this->checkCacheLastModified($lastModified);

        $beanETag = $this->ETag();
        $this->checkCacheETag($beanETag);

        $this->setHeader("Last-Modified", gmdate(SparkHTTPResponse::DATE_FORMAT, $lastModified));
        $this->setHeader("ETag", $beanETag);

        //prefer ETag as disposition filename
        $this->setDispositionFilename($beanETag);

        $cacheName = $this->cacheName();
        Debug::ErrorLog("Using blob CacheEntry[".$cacheName."]");

        //check if we have the data in cache (skip fetching blob data from DB and image processing if found)
        if ($this->cacheEntry && $this->cacheEntry->haveData()) {
            Debug::ErrorLog("Blob contents found in cache - sending cached blob as a response");
            $this->setHeader("X-Tag", "SparkCache");
            $this->sendCacheEntry($this->cacheEntry);
            exit;
        }

        //fully load the bean data including the blob field
        $this->loadBlob();

        //do the magic - ie image resize; also set content length header
        $this->process();

        //store to cache
        if ($this->cacheEntry) {
            Debug::ErrorLog("Storing cache file for this bean request");
            $this->cacheEntry->storeBuffer($this->object->buffer(), $lastModified);
            if ($this->bean->haveColumn("auth_context")) {
                $this->authEntry->store($this->authClass, $lastModified);
            }
            Debug::ErrorLog("Sending cache file as a response");
            $this->sendCacheEntry($this->cacheEntry);
        }
        else {
            //Debug::ErrorLog case only when cache is disabled
            Debug::ErrorLog("Using sendData as a response");
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
        return Spark::Hash($this->cacheName()."-".$this->getLastModified());
    }

    abstract protected function process(): void;
    abstract protected function cacheName() : string;


}