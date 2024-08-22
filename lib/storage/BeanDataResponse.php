<?php
include_once("storage/HTTPResponse.php");
include_once("storage/CacheFile.php");
include_once("storage/FileStorageObject.php");
include_once("auth/Authenticator.php");

abstract class BeanDataResponse extends HTTPResponse
{

    protected $className = "";
    protected $id = -1;
    protected $field = "";
    protected $row = array();

    /**
     * @var DBTableBean
     */
    protected $bean = NULL;

    protected $etag_parts = array();
    protected $disposition = "inline";

    protected $skip_cache = FALSE;

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

        $this->etag_parts[] = $this->className;
        $this->etag_parts[] = $this->id;
        $this->etag_parts[] = get_class($this);

        if (isset($_GET["field"])) {
            $this->field = $_GET["field"];
            $this->field_requested = TRUE;

            debug("Requested bean field: {$this->field}");
        }

        $this->etag_parts[] = $this->field;

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
        if (isset($this->headers["ETag"])) {
            debug("ETag already set. Nothing to do ...");
            return;
        }
        // set headers and etag
        $last_modified = $this->getBeanLastModified();

        //set last modified from bean
        $modified = gmdate(HTTPResponse::DATE_FORMAT, $last_modified);
        debug("Last-Modified: $modified");

        //keep one year ahead from request time
        $expires = gmdate(HTTPResponse::DATE_FORMAT, strtotime("+1 year"));
        debug("Expires: $expires");

        //add last modified to etag calculation
        $etag = md5(implode("|", $this->etag_parts) . "-" . $last_modified);
        debug("ETag: $etag");

        $this->setHeader("ETag", $etag);

        $this->setHeader("Expires", $expires);

        $this->setHeader("Last-Modified", $modified);

        $this->setHeader("Cache-Control", "max-age=31556952, must-revalidate");
    }

    /**
     * Set content type headers (content-type, content-disposition, content-transfer-encoding)
     * @return void
     */
    protected function fillContentHeaders() : void
    {
        $mime = "application/octet-stream";

        if (isset($this->row["mime"])) {
            $mime = $this->row["mime"];
        }

        $this->setHeader("Content-Type", $mime);

        if (isset($this->headers["ETag"])) {
            $filename = $this->headers["ETag"];
        }
        elseif (isset($this->row["filename"])) {
            $filename = $this->row["filename"];
        }
        else {
            $filename = md5(microtime_float());
        }

        $this->setHeader("Content-Disposition", "{$this->disposition}; filename=\"$filename\"");

        $this->setHeader("Content-Transfer-Encoding", "binary");
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

        //!
        if ($this->id==-1) {
            debug("Default value using last-modified from current date/time: " . $last_modified);
            return $last_modified;
        }

        $columns = array("timestamp", "date_upload", "date_updated");
        foreach($columns as $idx=>$name) {
            if (!$this->bean->haveColumn($name)) {
                unset($columns[$idx]);
            }
        }

        if (count($columns)<1) {
            debug("Using last-modified from current date/time: " . $last_modified);
        }
        else {

            $row = $this->bean->getByID($this->id, ...$columns);

            $found_time = false;
            foreach ($columns as $idx=>$name) {
                if (isset($row[$name]) && $row[$name]) {

                    if (strcasecmp($this->bean->columnType($name), "TIMESTAMP")==0) {
                        debug("Found timestamp column: ".$row[$name]);
                        $last_modified = strtotime($row[$name]);
                    }
                    else {
                        $last_modified = strtotime($row[$name]);
                    }

                    debug("Using last-modified from [$name]: " . $last_modified);
                    $found_time = true;
                    break;
                }
            }
            if (!$found_time) {
                debug("Using last-modified from current date/time: " . $last_modified);
            }

        }

        return $last_modified;
    }
    /**
     * Return the contents of bean data with key '$this->field'.
     * Using ETag logic - checks the disk cache and return 304
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

        //browser is sending ETag
        $requestETag = $this->requestETag();

        debug("Request ETag is: $requestETag");

        //early 304
        if ($requestETag) {

            if (STORAGE_CACHE_ENABLED && !$this->skip_cache) {
                $cacheFile = new CacheFile($requestETag, $this->className, $this->id);
                if ($cacheFile->exists()) {
                    debug("Matching cache file found for this request ETag - responding with HTTP/304");
                    //exit with 304 not modified
                    $this->sendNotModified();
                }
                debug("No cache file matches this request ETag");
            }

        }

        //calculate the ETag
        $this->fillCacheHeaders();

        if ($requestETag && strcmp($this->headers["ETag"], $requestETag)==0) {
            debug("Request ETag match current bean ETag - responding with HTTP/304");
            //exit with 304 not modified
            $this->sendNotModified();
        }
        else {
            debug("Request ETag missing or not matching");
        }

        //fully load the bean data including the blob field
        $this->loadBeanData();
        $this->unpackStorageObject();

        //fill remaining headers
        $this->fillContentHeaders();

        if (STORAGE_CACHE_ENABLED && !$this->skip_cache) {
            $cacheFile = new CacheFile($this->headers["ETag"], $this->className, $this->id);

            //check if we have the ETag in cache so to skip image processing
            if ($cacheFile->exists()) {
                debug("Sending matched ETag file from cache");
                $this->setHeader("X-Tag", "SparkCache");
                //will exit after sending
                $this->sendFile($cacheFile->fileName());
            }

            //do the magic - ie image resize; also set content length header
            $this->processData();

            //store to cache
            $cacheFile->store($this->data);

            $this->sendFile($cacheFile->fileName());
        }
        else {
            //do the magic - ie image resize; also set content length header
            $this->processData();
            debug("Sending data");
            $this->sendData();
        }

        exit;
    }

    abstract protected function processData();

}
