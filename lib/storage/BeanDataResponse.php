<?php
include_once("storage/HTTPResponse.php");
include_once("storage/CacheFile.php");
include_once("storage/FileStorageObject.php");

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

    public $skip_cache = false;

    /**
     * This flag is set when request URI contains specific key name to return from the result row.
     * Custom key name from the result row is allowed only if contents are of type StorageObject
     * To limit arbitrary data access
     * @var bool
     */
    protected $field_requested = false;

    public function __construct(int $id, string $className)
    {
        $this->id = $id;
        $this->className = $className;



        @include_once("class/beans/" . $this->className . ".php");

        @include_once("beans/" . $this->className . ".php");

        if (!class_exists($this->className,false)) {
            throw new Exception("Bean class not found: " . $this->className);
        }

        $this->bean = new $this->className();

        $this->etag_parts[] = $this->className;
        $this->etag_parts[] = $this->id;
        $this->etag_parts[] = get_class($this);

        if (isset($_GET["field"])) {
            $this->field = $_GET["field"];
            $this->field_requested = true;
        }

        //sub-classes set $this->field
        if (!$this->bean->haveField($this->field)) {
            throw new Exception("Bean does not support this field");
        }

        $this->etag_parts[] = $this->field;

        //        $stypes = $this->bean->storageTypes();
        //        if (!array_key_exists($this->blob_field, $stypes)) {
        //            throw new Exception("No such blob field found");
        //        }



    }

    protected function authorizeAccess()
    {

        if (!isset($this->row["auth_context"])) return;
        if (strlen($this->row["auth_context"]) < 1) return;

        debug("Protected object requested ...");

        Session::Start();

        Authenticator::AuthorizeResource($this->row["auth_context"], $this->row, true);

        Session::Close();
    }

    protected function loadBeanData()
    {
        if ($this->id == -1) {

            $funcname = "Default_" . $this->className;

            if (is_callable($funcname)) {
                $funcname($this->row);
            }
            else throw new Exception("No default value for this class");

        }
        else {
            debug("Fetching ID: ".$this->id." Bean: ".get_class($this->bean));
            $this->row = $this->bean->getByID($this->id);
        }

        if (!isset($this->row[$this->field])) {
            throw new Exception("No data for this blob field");
        }
    }

    protected function unpackStorageObject()
    {
        debug("...");

        $object = @unserialize($this->row[$this->field]);

        if ($object instanceof StorageObject) {

            debug("Unpacked: ".get_class($object));

            //replace result with storageobject data
            $this->row = array();
            //image resizer expects row["photo"]

            $object->deconstruct($this->row, false);

        }
        else {

            debug("No object unserialized from field[$this->field]");

            //Limit arbitrary data access from the result row
            if ($this->field_requested) {
                throw new Exception("Incorrect request received. Source data type is not StorageObject.");
            }
            //continue for objects transacted to db as dbrow and the default key name will be used (photo or data)

        }
    }

    /**
     * All content will be hashed and used as ETag header field
     * @return array
     */
    protected function getETagParts() : array
    {
        return $this->etag_parts;
    }

    protected function fillHeaders()
    {
        // set headers and etag
        $last_modified = gmdate("D, d M Y H:i:s T");

        if (isset($this->row["date_upload"])) {
            $last_modified = gmdate("D, d M Y H:i:s T", strtotime($this->row["date_upload"]));
        }
        else if (isset($this->row["date_updated"])) {
            $last_modified = gmdate("D, d M Y H:i:s T", strtotime($this->row["date_updated"]));
        }

        $etag = md5(implode("|", $this->getETagParts()) . "-" . $last_modified);

        $this->setHeader("ETag", $etag);
        //always keep one year ahead from request time
        $this->setHeader("Expires", gmdate("D, d M Y H:i:s T", strtotime("+1 year", strtotime($last_modified))));
        $this->setHeader("Last-Modified", $last_modified);

        $mime = "application/octet-stream";
        if (isset($this->row["mime"])) {
            $mime = $this->row["mime"];
        }

        $this->setHeader("Content-Type", $mime);

        $this->setHeader("Cache-Control", "no-cache, must-revalidate");

        //header("Pragma: ".$this->headers["etag"]);

        $filename = $etag;
        if (isset($this->row["filename"])) {
            $filename = $this->row["filename"];
        }

        $this->setHeader("Content-Disposition", "{$this->disposition}; filename=\"$filename\"");

        $this->setHeader("Content-Transfer-Encoding", "binary");

    }

    protected function isBrowserCached()
    {

        // error_log("Storage::checkCache last_modified: $last_modified | expire: $expire | etag: $etag",4);

        // check if the last modified date sent by the client is the the same as
        // the last modified date of the requested file. If so, return 304 header
        // and exit.

        // check if the Etag sent by the client is the same as the Etag of the
        // requested file. If so, return 304 header and exit.

        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            //error_log("Storage::checkCache HTTP_IF_NONE_MATCH: ".$_SERVER['HTTP_IF_NONE_MATCH'],4);
            $pos = strpos($_SERVER['HTTP_IF_NONE_MATCH'], $this->getHeader("ETag"));
            if ($pos !== FALSE) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function send(bool $doExit = true)
    {
        debug("Class: ".$this->className." ID: ".$this->id." Field: ".$this->field);

        $this->loadBeanData();

        $this->authorizeAccess();

        $this->unpackStorageObject();

        $this->fillHeaders();

        $cacheFile = new CacheFile($this->headers["ETag"], $this->className, $this->id);

        if (!$this->skip_cache) {

            if ($this->isBrowserCached()) {
                $this->clearHeaders();
                $this->setHeader("HTTP/1.1 304 Not Modified");
                $this->setHeader("Last-Modified", gmdate("D, d M Y H:i:s T"));
                $this->setHeader("Cache-Control", "no-cache, must-revalidate");
                parent::send(true);
                //header("Pragma: ".$this->headers["etag"]);
                //header("ETag: $etag");
            }


            if ($cacheFile->exists()) {
                $ret = $cacheFile->load();
                $this->setHeader("X-Tag", "CacheFile");
                $this->setData($ret[CacheFile::KEY_DATA], $ret[CacheFile::KEY_SIZE]);
                parent::send(true);
            }

        }

        $this->processData();

        $cacheFile->store($this->getData());

        parent::send(true);
    }

    abstract protected function processData();

}