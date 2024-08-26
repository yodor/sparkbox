<?php
include_once("storage/SparkHTTPResponse.php");
include_once("storage/CacheEntry.php");
include_once("storage/FileStorageObject.php");
include_once("auth/Authenticator.php");

abstract class BeanDataResponse extends SparkHTTPResponse
{

    protected string $className = "";
    protected int $id = -1;
    protected string $field = "";
    protected array $row = array();

    protected ?CacheEntry $cacheEntry;

    /**
     * @var DBTableBean
     */
    protected DBTableBean $bean;

    protected bool $skip_cache = FALSE;

    protected int $last_modified = -1;
    /**
     * This flag is set when request URI contains specific field name to return from the result row.
     * Custom key name from the result row is allowed only if contents are of type StorageObject
     * To limit arbitrary data access
     * @var bool
     */
    protected $field_requested = FALSE;

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

            debug("Requested bean field: {$this->field}");
        }

        $this->cacheEntry = NULL;

        if (STORAGE_CACHE_ENABLED && !$this->skip_cache) {
            $this->cacheEntry = new CacheEntry($this->cacheName(), $this->className, $this->id);
        }


    }

    /**
     * @throws Exception
     */
    protected function authorizeAccess()
    {
        debug("authorizeAccess ...");

        if (!$this->bean->haveColumn("auth_context")) {
            debug("No auth_context column defined");
            return;
        }

        //exclude blob type columns
        $beanColumns = $this->bean->columns();
        foreach ($beanColumns as $columnName => $storageType) {
            if (strpos($storageType, "blob") !== FALSE) {
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
     * Load full result from database into $this->row.
     * If requested id=-1 try to call function 'Default_$this->className' to construct default data result and set as $this->row.
     * @return void
     * @throws Exception
     */
    protected function loadBeanData()
    {
        if ($this->id == -1) {

            $funcname = "Default_" . $this->className;

            if (is_callable($funcname)) {
                $funcname($this->row);
                return;
            }
            throw new Exception("No default value for this class");
        }

        debug("Loading ID: " . $this->id . " from " . $this->className);

        $this->row = $this->bean->getByID($this->id);
        debug("Data keys loaded: ", array_keys($this->row));

    }

    protected function unpackStorageObject()
    {
        debug("...");

        $object = @unserialize($this->row[$this->field]);

        if ($object instanceof StorageObject) {

            debug("Unpacked: " . get_class($object));

            $object->setDataKey($this->field);
            $object->deconstruct($this->row, FALSE);

        }
        else {

            debug("Field[$this->field] does not contain StorageObject");

            //Limit arbitrary data access from the result row
            if ($this->field_requested) {
                throw new Exception("Field does not contain StorageObject");
            }
            //continue for objects transacted to db as dbrow and the default key name will be used (photo or data)

        }
    }

    /**
     * Prepare and set 'ETag', 'Last-Modified' and 'Expires' HTML header fields.
     * If ETag is already set do nothing.
     * @return void
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

        $this->setHeader("Expires", $expires);

        $this->setHeader("Last-Modified", $modified);

        $this->setHeader("Cache-Control", "max-age=31556952, must-revalidate");
    }

    /**
     * Set content type headers (content-type, content-disposition, content-transfer-encoding)
     * Called after data is set and if cache is disabled
     * @return void
     */
    protected function fillContentHeaders() : void
    {

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($this->data);

        if ($mime) {
            $this->setHeader("Content-Type", $mime);
        }
        else {
            $this->setHeader("Content-Type", "application/octet-stream");
        }

        if (isset($this->row["filename"])) {
            $filename = $this->row["filename"];
        }
        elseif (isset($this->headers["ETag"])) {
            $filename = $this->headers["ETag"];
        }
        else {
            $filename = sparkHash(microtime_float());
        }

        $this->setHeader("Content-Disposition", "{$this->disposition}; filename=\"$filename\"");
        $this->setHeader("Content-Transfer-Encoding", "binary");
    }


    /**
     * Get last modified from cache file or from bean
     *
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
     * Fetches columns timestamp, date_upload or date_updated of this bean to construct the last modified time
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
        foreach ($columns as $idx=>$name) {
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
     * @throws Exception
     */
    public function send(bool $doExit = TRUE)
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
        $this->checkCacheETag($this->ETag());

        $cacheName = $this->cacheName();
        debug("Cache name is: ".$cacheName);

        if ($this->cacheEntry && $this->cacheEntry->getFile()->exists()) {
            //check if we have the data in cache (skip fetching blob data from DB and image processing if found)
            debug("Bean data found in cache - sending cache file as a response");
            $this->fillCacheHeaders();
            $this->setHeader("X-Tag", "SparkCache");
            $this->sendFile($this->cacheEntry->getFile(), $beanETag);
            exit;
        }

        //fully load the bean data including the blob field
        $this->loadBeanData();
        $this->unpackStorageObject();

        //do the magic - ie image resize; also set content length header
        $this->processData();

        //cache headers
        $this->fillCacheHeaders();

        //store to cache
        if ($this->cacheEntry) {
            debug("Storing cache file for this bean request");
            $this->cacheEntry->store($this->data, $lastModified);
            debug("Sending cache file as a response");
            $this->sendFile($this->cacheEntry->getFile(), $beanETag);
        }
        else {
            debug("Using sendData as a response");
            //fill remaining headers - call after data is already set
            $this->fillContentHeaders();
            $this->sendHeaders();
            $this->sendData();
            exit;
        }


    }

    protected function ETag() : string
    {
        return sparkHash($this->cacheName()."-".$this->getLastModified());
    }

    abstract protected function processData();
    abstract protected function cacheName() : string;


}
