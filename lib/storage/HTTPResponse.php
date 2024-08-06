<?php

class HTTPResponse
{
    const SEND_BUFFER = 4096;

    protected $headers = array();

    protected $data = "";
    protected $dataSize = -1;

    public const DATE_FORMAT = "D, d M Y H:i:s T";

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
     * Send 304 not modified and exit
     * @return void
     */
    public function sendNotModified()
    {
        //RFC https://datatracker.ietf.org/doc/html/rfc7232#section-4.1
        //Cache-Control, Content-Location, Date, ETag, Expires, and Vary
        $this->clearHeaders();
        $this->setHeader("HTTP/1.1 304 Not Modified");
        $this->setHeader("Cache-Control", "max-age=31556952, must-revalidate");

        $expires = gmdate(HTTPResponse::DATE_FORMAT, strtotime("+1 year"));
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
        exit;
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
            }
            else {
                header("$key: $val");
            }
        }

        debug("Response headers sent");
    }

    /**
     * Write contents of file '$fileName' and exit. Calls sendHeaders()
     * @param string $fileName
     * @return void
     */
    protected function sendFile(string $fileName)
    {

        $this->sendHeaders();

        $handle = fopen($fileName, 'r');
        flock($handle, LOCK_SH);
        fpassthru($handle);

        flock($handle, LOCK_UN);
        fclose($handle);

        debug("Sending complete: $fileName");
        exit;
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
