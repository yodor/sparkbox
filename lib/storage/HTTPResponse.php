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
        $this->clearHeaders();
        $this->setHeader("HTTP/1.1 304 Not Modified");
        $this->setHeader("Last-Modified", gmdate("D, d M Y H:i:s T"));
        $this->setHeader("Cache-Control", "no-cache, must-revalidate");
        $this->sendHeaders();
        exit;
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