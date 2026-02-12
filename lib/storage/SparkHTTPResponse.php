<?php

class SparkHTTPResponse
{

    protected array $headers = array();

    public const string DATE_FORMAT = "D, d M Y H:i:s T";

    protected string $disposition = "inline";
    protected string $disposition_filename = "";

    public function __construct(int $max_age=3600, int $stale_while_revalidate=3600)
    {
        $this->setHeader("Content-Transfer-Encoding", "binary");
        //one hour expiration
        $this->setHeader("Cache-Control", "public, must-revalidate, must-understand, max-age=$max_age, stale-while-revalidate=$stale_while_revalidate");
    }

    public function setDispositionFilename(string $disposition_filename) : void
    {
        $this->disposition_filename = $disposition_filename;
    }
    public function getDispositionFilename() : string
    {
        return $this->disposition_filename;
    }
    public function setDisposition(string $disposition) : void
    {
        $this->disposition = $disposition;
    }
    public function getDisposition() : string
    {
        return $this->disposition;
    }
    /**
     * Clear all assigned headers
     * @return void
     */
    public function clearHeaders() : void
    {
        $this->headers = array();
    }

    /**
     * Assign http response header for sending during sendHeaders call
     * @param string $field Response header name
     * @param string $value Response header value
     * @return void
     */
    public function setHeader(string $field, string $value = "") : void
    {
        $this->headers[$field] = $value;
    }

    public function getHeader(string $field): string
    {
        if ($this->haveHeader($field)) throw new Exception("Header not set");
        return $this->headers[$field];
    }

    public function removeHeader(string $field) : void
    {
        if (isset($this->headers[$field])) unset($this->headers[$field]);
    }

    public function haveHeader(string $field) : bool
    {
        return isset($this->headers[$field]);
    }

    /**
     * Compare request IF_MODIFIED_SINCE (lastModified) with '$lastModified' timestamp
     * Call sendNotModified on match
     * @param int $lastModified Compare with this timestamp
     * @return void
     */
    public function checkCacheLastModifed(int $lastModified) : void
    {
        $modifiedSince = strtotime($this->requestModifiedSince());

        Debug::ErrorLog("Request IF_MODIFIED_SINCE: ".$modifiedSince);
        Debug::ErrorLog("Response last_modified: ".$lastModified);
        if ($modifiedSince !== FALSE) {
            if ($lastModified<=$modifiedSince) {
                Debug::ErrorLog("Request IF_MODIFIED_SINCE matching - responding with HTTP/304");
                $this->sendNotModified();
                exit;
            }
        }
    }


    /**
     * Compare request ETag with this response ETag
     * Call sendNotModified on match
     * @param string $ETag Compare with this ETag
     * @return void
     */
    public function checkCacheETag(string $ETag) : void
    {
        //browser is sending ETag
        $requestETag = $this->requestETag();
        Debug::ErrorLog("Request ETag is: ".$requestETag);
        Debug::ErrorLog("Response ETag is: ".$ETag);
        if (strcasecmp($requestETag, $ETag)==0) {
            Debug::ErrorLog("Request ETag match response ETag - responding with HTTP/304");
            $this->sendNotModified();
            exit;
        }
    }

    /**
     * Send HTTP 304 response
     * Set headers Last-Modified and ETag using $this->requestModifiedSince() and $this->requestETag();
     * Send all headers set by calling sendHeaders()
     * @return void
     */
    public function sendNotModified() : void
    {

        $this->setHeader("HTTP/2 304 Not Modified");

        $requestModifiedSince = $this->requestModifiedSince();
        if ($requestModifiedSince) {
            $this->setHeader("Last-Modified", $requestModifiedSince);
        }

        $requestETag = $this->requestETag();
        if ($requestETag) {
            $this->setHeader("ETag", $requestETag);
        }

        $this->sendHeaders();
    }

    /**
     * Return contents of request header (HTTP_IF_NONE_MATCH) - ETag
     * @return string
     */
    public function requestETag() : string
    {
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return $_SERVER['HTTP_IF_NONE_MATCH'];
        }
        return "";
    }

    /**
     * Return contents of request header (HTTP_IF_MODIFIED_SINCE) - last-modified
     * @return string
     */
    public function requestModifiedSince() : string
    {
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            return $_SERVER['HTTP_IF_MODIFIED_SINCE'];
        }
        return "";
    }

    /**
     * Set all the headers to this response using $this->headers as source data
     * @return void
     */
    protected function sendHeaders() : void
    {
        foreach ($this->headers as $key => $val) {
            if (strlen($key) < 1) continue;
            if (strlen($val) < 1) {
                header($key);
                //Debug::ErrorLog("Header: $key");
            }
            else {
                header("$key: $val");
                //Debug::ErrorLog("Header: $key: $val");
            }
        }

        Debug::ErrorLog("Response headers set");
    }

    public function sendCacheEntry(CacheEntry $cacheEntry) : void
    {
        if ($cacheEntry instanceof FileCacheEntry) {
            $this->sendFile($cacheEntry->getFile());
        }
        else if ($cacheEntry instanceof DBCacheEntry) {
            $this->sendData($cacheEntry->getBuffer());
        }
        else {
            throw new Exception("Unsupported CacheEntry type");
        }
    }
    /**
     * Output contents of file
     * @param SparkFile $file Contents of file to output
     * @return void
     * @throws Exception
     */
    public function sendFile(SparkFile $file) : void
    {

        Debug::ErrorLog("Sending file: ".$file->getAbsoluteFilename());

        $this->setHeader("Content-Type", $file->getMIME());
        $this->setHeader("Content-Length", $file->length());

        $filename = $this->disposition_filename;
        if (empty($filename)) {
            $filename = $file->getFilename();
        }

        Debug::ErrorLog("Using disposition filename: ".$filename);

        $this->setHeader("Content-Disposition", $this->disposition."; filename=\"".$filename."\"");

        if (!$this->haveHeader("Last-Modified")) {
            $this->setHeader("Last-Modified", gmdate(SparkHTTPResponse::DATE_FORMAT, $file->lastModified()));
        }

        if (!$this->haveHeader("ETag")) {
            $this->setHeader("ETag", $file->getEtag());
        }

        $this->sendHeaders();

        $file->open('r');
        $file->lock(LOCK_SH);
        $file->passthru();
        $file->lock(LOCK_UN);
        $file->close();
        Debug::ErrorLog("Sending complete ...");
    }

    /**
     * Output contents of $this->data
     * @param DataBuffer $buffer
     * @return void
     */
    public function sendData(DataBuffer $buffer) : void
    {
        if ($buffer->length()<1) return;

        Debug::ErrorLog("Sending DataBuffer ...");

        $filename = $this->disposition_filename;
        if(!empty($filename)) {
            Debug::ErrorLog("Using disposition filename: ".$filename);
            $this->setHeader("Content-Disposition", $this->disposition."; filename=\"".$filename."\"");
        }
        else {
            Debug::ErrorLog("Disposition filename not set - not using disposition header");
        }

        $this->setHeader("Content-Type", $buffer->mime());
        $this->setHeader("Content-Length", $buffer->length());

        $this->sendHeaders();

        echo $buffer->data();

        Debug::ErrorLog("Data sending completed: ".$buffer->length()." bytes sent");

    }
}