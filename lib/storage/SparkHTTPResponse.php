<?php

class SparkHTTPResponse
{
    protected array $headers = array();

    protected string $data = "";
    protected int $dataSize = -1;

    public const DATE_FORMAT = "D, d M Y H:i:s T";

    protected string $disposition = "inline";
    protected string $disposition_filename = "";

    public function __construct()
    {
        $this->setHeader("Content-Transfer-Encoding", "binary");
        //one hour expiration
        $this->setHeader("Cache-Control", "public, max-age=3600, stale-while-revalidate=3600");
    }

    public function setDispositionFilename(string $disposition_filename)
    {
        $this->disposition_filename = $disposition_filename;
    }
    public function getDispositionFilename() : string
    {
        return $this->disposition_filename;
    }
    public function setDisposition(string $disposition)
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
    public function clearHeaders()
    {
        $this->headers = array();
    }

    /**
     * Assign http response header for sending during sendHeaders call
     * @param string $field Response header name
     * @param string $value Response header value
     * @return void
     */
    public function setHeader(string $field, string $value = "")
    {
        $this->headers[$field] = $value;
    }

    public function getHeader(string $field): string
    {
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

    public function setData(string $data, int $dataSize)
    {
        $this->data = $data;
        $this->dataSize = $dataSize;
        if ($this->dataSize > 0) {
            $this->setHeader("Content-Length", $this->dataSize);
        }
    }

    /**
     * Return current data assigned
     * @return string
     */
    public function getData() : string
    {
        return $this->data;
    }

    /**
     * Return size of current data
     * @return int
     */
    public function getSize() : int
    {
        return $this->dataSize;
    }

    /**
     * Default implementation - send all headers assigned and data
     * @param bool Default true - Exit after sending
     */
    public function send(bool $doExit = TRUE)
    {
        $this->sendHeaders();
        $this->sendData();
        if ($doExit) {
            exit;
        }
    }

    /**
     * Check request IF_MODIFIED_SINCE matches current last-modified and call sendNotModified
     * @param int $lastModified - Current data last modified timestamp
     * @return void
     */
    protected function checkCacheLastModifed(int $lastModified)
    {
        $modifiedSince = strtotime($this->requestModifiedSince());

        debug("Request IF_MODIFIED_SINCE: ".$modifiedSince);
        debug("Response last_modified: ".$lastModified);
        if ($modifiedSince !== FALSE) {
            if ($lastModified<=$modifiedSince) {
                debug("Request IF_MODIFIED_SINCE matching - responding with HTTP/304");
                $this->sendNotModified();
                exit;
            }
        }
    }

    /**
     * Check if request ETag matches current data ETag and call sendNotModified
     * @param string $etag Current data ETag
     * @return void
     */
    protected function checkCacheETag(string $etag)
    {
        //browser is sending ETag
        $requestETag = $this->requestETag();
        debug("Request ETag is: ".$requestETag);
        debug("Response ETag is: ".$etag);
        if (strcasecmp($requestETag, $etag)==0) {
            debug("Request ETag match response ETag - responding with HTTP/304");
            $this->sendNotModified();
            exit;
        }
    }

    /**
     * Set 304 not modified headers
     * @return void
     */
    public function sendNotModified()
    {

        $this->setHeader("HTTP/2 304 Not Modified");

        $req_modified = $this->requestModifiedSince();
        if ($req_modified) {
            $this->setHeader("Last-Modified", $req_modified);
        }

        $etag = $this->requestETag();
        if ($etag) {
            $this->setHeader("ETag", $etag);
        }

        $this->sendHeaders();
    }

    /**
     * Return contents of request header (HTTP_IF_NONE_MATCH) - ETag
     * @return string
     */
    protected function requestETag() : string
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
    protected function requestModifiedSince() : string
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
    protected function sendHeaders()
    {
        foreach ($this->headers as $key => $val) {
            if (strlen($key) < 1) continue;
            if (strlen($val) < 1) {
                header($key);
                //debug("Header: $key");
            }
            else {
                header("$key: $val");
                //debug("Header: $key: $val");
            }
        }

        debug("Response headers set");
    }

    /**
     * Output contents of file
     * @param SparkFile $file Contents of file to output
     * @return void
     * @throws Exception
     */
    protected function sendFile(SparkFile $file)
    {

        debug("Sending file: ".$file->getAbsoluteFilename());

        $this->setHeader("Content-Type", $file->getMIME());
        $this->setHeader("Content-Length", $file->length());

        $filename = $this->disposition_filename;
        if (empty($filename)) {
            $filename = $file->getFilename();
        }

        debug("Using disposition filename: ".$filename);

        $this->setHeader("Content-Disposition", $this->disposition."; filename=\"".$filename."\"");

        $last_modified = gmdate(SparkHTTPResponse::DATE_FORMAT, $file->lastModified());
        $this->setHeader("Last-Modified", $last_modified);

        if (!$this->haveHeader("ETag")) {
            $etag = sparkHash($file->getFilename()."-".$last_modified);
            $this->setHeader("ETag", $etag);
        }

        $this->sendHeaders();

        $file->open('r');
        $file->lock(LOCK_SH);
        $file->passthru();
        $file->lock(LOCK_UN);
        $file->close();
        debug("Sending complete ...");
    }

    /**
     * Output contents of $this->data
     * @return void
     */
    protected function sendData()
    {
        if ($this->dataSize < 1) return;

        echo $this->data;

        debug("Data sending completed: $this->dataSize bytes sent");

    }
}
