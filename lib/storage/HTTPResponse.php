<?php


class HTTPResponse
{
    const SEND_BUFFER = 4096;

    protected $headers = array();

    protected $data = "";
    protected $dataSize = 0;

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

    public function setData($data, int $dataSize)
    {
        $this->data = $data;
        $this->dataSize = $dataSize;
        if ($this->dataSize>0) {
            $this->setHeader("Content-Length", $this->dataSize);
        }
    }

    public function getData()
    {
        return $this->data;
    }

    public function getSize()
    {
        return strlen($this->data);
    }

    /**
     * Send headers and data
     * @param bool Exit after sending if is set to true
     */
    public function send(bool $doExit = true)
    {
        $this->sendHeaders();
        $this->sendData();
        if ($doExit) {
            exit;
        }
    }

    protected function sendHeaders()
    {
        foreach ($this->headers as $key=>$val) {
            if (strlen($key)<1)continue;
            if (strlen($val)<1) {
                header($key);
            }
            else {
                header("$key: $val");
            }
        }

        debug("Headers sent");
    }

    protected function sendData()
    {
        if ($this->dataSize<1) return;

        $fp = fopen("php://output", "wb");

        if ($fp) {

            $written = 0;
            $fwrite = 0;

            for ($written = 0; $written < $this->dataSize; $written += $fwrite) {
                $fwrite = fwrite($fp, substr($this->data, $written, HTTPResponse::SEND_BUFFER));
                //error writing
                if ($fwrite === false) {
                    debug("Error: written only $written of {$this->row["size"]} bytes");
                    break;
                }
                else {
                    @fflush($fp);
                }
            }

            @fclose($fp);

            debug("Sent $written of {$this->dataSize} bytes");
        }
        else {
            debug("Sending using 'print'");
            print($this->data);

        }

        debug("Data sending completed");

    }
}