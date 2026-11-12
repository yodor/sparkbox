<?php

class SparkHTTPResponse
{

    protected array $headers = array();

    public const string DATE_FORMAT = "D, d M Y H:i:s T";

    protected string $disposition = "inline";
    protected string $disposition_filename = "";

    const int MAX_AGE_YEAR = 31536000;
    const int MAX_AGE_HOUR = 3600;
    const int MAX_AGE_WEEK = 604800;
    public function __construct(int $max_age=SparkHTTPResponse::MAX_AGE_YEAR, int $stale_while_revalidate=SparkHTTPResponse::MAX_AGE_WEEK)
    {
        //start on clean
        header_remove();

        //base headers
        $this->setHeader("Content-Transfer-Encoding", "binary");

        //variables from constructor - 1 hour default expiration
        $cacheControl = "public, must-revalidate, must-understand, max-age=$max_age, stale-while-revalidate=$stale_while_revalidate, immutable";
        $this->setHeader("Cache-Control", $cacheControl);

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
        $field = trim($field);
        $this->headers[$field] = $value;
    }

    public function getHeader(string $field): string
    {
        $field = trim($field);

        if ($this->haveHeader($field)) return $this->headers[$field];

        throw new Exception("Header not set");
    }

    public function removeHeader(string $field) : void
    {
        $field = trim($field);
        if ($this->haveHeader($field)) unset($this->headers[$field]);
    }

    public function haveHeader(string $field) : bool
    {
        $field = trim($field);
        return isset($this->headers[$field]);
    }

    /**
     * Compare request IF_MODIFIED_SINCE (lastModified) with '$lastModified' timestamp
     * Call sendNotModified on match
     * @param int $lastModified Compare with this timestamp
     * @return void
     */
    public function checkCacheLastModified(int $lastModified) : void
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
     * Compare the request If-None-Match list with '$ETag' (unquoted value)
     * Call sendNotModified on match
     * @param string $ETag Compare with this ETag
     * @return void
     */
    public function checkCacheETag(string $ETag) : void
    {
        $tags = $this->requestETags();
        Debug::ErrorLog("Request ETags: ".implode(", ", $tags)." | Response ETag: ".$ETag);
        if (in_array("*", $tags, true) || in_array($ETag, $tags, true)) {
            Debug::ErrorLog("Request ETag match response ETag - responding with HTTP/304");
            $this->sendNotModified($ETag);
            exit;
        }
    }

    /**
     * Conditional request handling (RFC 9110 13.2.2):
     * If the request has If-None-Match only the ETag decides and If-Modified-Since is ignored,
     * otherwise If-Modified-Since is compared with '$lastModified'.
     * Sends HTTP 304 and exits on match.
     * @param string $ETag Unquoted ETag value of the current representation
     * @param int $lastModified Unix timestamp of the current representation
     * @return void
     */
    public function checkNotModified(string $ETag, int $lastModified) : void
    {
        if (count($this->requestETags()) > 0) {
            $this->checkCacheETag($ETag);
            return;
        }
        $this->checkCacheLastModified($lastModified);
    }

    /**
     * Parse If-None-Match into a list of unquoted ETag values. W/ prefixes are dropped (weak comparison).
     * @return array<string>
     */
    public function requestETags() : array
    {
        $tags = array();
        foreach (explode(",", $this->requestETag()) as $tag) {
            $tag = trim($tag);
            if ($tag === "") continue;
            if (str_starts_with($tag, "W/")) $tag = substr($tag, 2);
            $tags[] = trim($tag, '"');
        }
        return $tags;
    }

    /**
     * Format ETag value for the ETag header - quoted as required by RFC 9110
     * @param string $ETag
     * @return string
     */
    public static function QuoteETag(string $ETag) : string
    {
        return '"' . trim($ETag, '"') . '"';
    }

    /**
     * Send HTTP 304 response with all headers set so far.
     * '$ETag' (unquoted) is sent as the response ETag when given, the request If-Modified-Since is
     * echoed as Last-Modified when no Last-Modified header is set yet.
     * @param string $ETag
     * @return void
     */
    public function sendNotModified(string $ETag = "") : void
    {
        http_response_code(304);

        if ($ETag !== "") {
            $this->setHeader("ETag", SparkHTTPResponse::QuoteETag($ETag));
        }

        $requestModifiedSince = $this->requestModifiedSince();
        if ($requestModifiedSince && !$this->haveHeader("Last-Modified")) {
            $this->setHeader("Last-Modified", $requestModifiedSince);
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
        $filename = "";
        $line = "";
        $result = headers_sent($filename, $line);
        if ($result) {
            throw new Exception("Headers already sent from: $filename:$line");
        }

        foreach ($this->headers as $key => $val) {
            if (strlen($key) < 1) continue;
            if (strlen($val) < 1) {
                header($key,true);
                //Debug::ErrorLog("Header: $key");
            }
            else {
                header("$key: $val", true);
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
            $this->setHeader("ETag", SparkHTTPResponse::QuoteETag($file->getEtag()));
        }

        // Disable zlib output compression (prevents full-response buffering for gzip)
        ini_set('zlib.output_compression', '0');

        // Clear any existing output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Enable implicit flushing (automatically flushes after every output block)
        ob_implicit_flush(true);

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