<?php

class SparkHTTPResponse
{
    const SEND_BUFFER = 4096;

    protected $headers = array();

    protected $data = "";
    protected $dataSize = -1;

    public const DATE_FORMAT = "D, d M Y H:i:s T";

    protected string $disposition = "inline";


    public function __construct()
    {

    }

    public function clearHeaders()
    {
        $this->headers = array();
    }

    public function setHeader(string $field, string $value = "")
    {
        $this->headers[$field] = $value;
    }

    public function getHeader(string $field): string
    {
        return $this->headers[$field];
    }

    public function setData(string $data, int $dataSize)
    {
        $this->data = $data;
        $this->dataSize = $dataSize;
        if ($this->dataSize > 0) {
            $this->setHeader("Content-Length", $this->dataSize);
        }
    }

    public function getData()
    {
        return $this->data;
    }

    public function getSize()
    {
        return $this->dataSize;
    }

    /**
     * Send headers and data
     * @param bool Exit after sending if is set to true
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
     * Set 304 not modified headers
     * @return void
     */
    public function sendNotModified()
    {
        //RFC https://datatracker.ietf.org/doc/html/rfc7232#section-4.1
        //Cache-Control, Content-Location, Date, ETag, Expires, and Vary
        $this->clearHeaders();
        $this->setHeader("HTTP/2 304 Not Modified");
        $this->setHeader("Cache-Control", "max-age=31556952, must-revalidate");

        $expires = gmdate(SparkHTTPResponse::DATE_FORMAT, strtotime("+1 year"));
        $this->setHeader("Expires", $expires);

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
     * Return contents of the ETag request header field (HTTP_IF_NONE_MATCH)
     * @return string
     */
    protected function requestETag() : string
    {
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return $_SERVER['HTTP_IF_NONE_MATCH'];
        }
        return "";
    }

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
     * Write contents of file '$fileName' and exit. Calls sendHeaders()
     * @param string $fileName
     * @return void
     */
    protected function sendFile(string $file, string $name="")
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file);

        if ($mime) {
            $this->setHeader("Content-Type", $mime);
        }
        else {
            $this->setHeader("Content-Type", "application/octet-stream");
        }

        if (empty($name)) {
            $name = $file;
        }
        $this->setHeader("Content-Length", filesize($file));
        $this->setHeader("Content-Disposition", $this->disposition."; filename=\"".basename($name)."\"");
        $this->setHeader("Content-Transfer-Encoding", "binary");

        $this->sendHeaders();

        $handle = fopen($file, 'r');
        if ($handle !== FALSE) {
            flock($handle, LOCK_SH);
            fpassthru($handle);
            flock($handle, LOCK_UN);
            fclose($handle);
            debug("Sending complete: $file");
        }
        else {
            throw new Exception("Unable to open file");
        }
    }

    /**
     * Write contents of $this->data and continue
     * @return void
     */
    protected function sendData()
    {
        if ($this->dataSize < 1) return;

        echo $this->data;

        debug("Data sending completed: $this->dataSize bytes sent");

    }
}
