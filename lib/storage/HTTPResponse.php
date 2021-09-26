<?php

class HTTPResponse
{
    const SEND_BUFFER = 4096;

    protected $headers = array();

    protected $data = "";
    protected $dataSize = -1;

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

    public function sendNotModified()
    {
        //RFC https://datatracker.ietf.org/doc/html/rfc7232#section-4.1
        //Cache-Control, Content-Location, Date, ETag, Expires, and Vary
        $this->clearHeaders();
        $this->setHeader("HTTP/1.1 304 Not Modified");
        $this->setHeader("Cache-Control", "no-cache, must-revalidate");

        $expires = gmdate(BeanDataResponse::DATE_FORMAT, strtotime("+1 year"));
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

    protected function requestETag()
    {
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return $_SERVER['HTTP_IF_NONE_MATCH'];
        }
        return "";
    }
    protected function requestModifiedSince()
    {
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            return $_SERVER['HTTP_IF_MODIFIED_SINCE'];
        }
        return "";
    }
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

        debug("Headers sent");
    }

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

    protected function sendData()
    {
        if ($this->dataSize < 1) return;

        echo $this->data;

        debug("Data sending completed: $this->dataSize bytes sent");

    }
}