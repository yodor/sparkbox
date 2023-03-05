<?php
class SparkFile {

    protected string $filename;
    protected string $path;
    protected finfo $finfo;
    protected $handle = NULL;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
        $this->path = INSTALL_PATH;
        $this->finfo = new finfo(FILEINFO_MIME_TYPE);
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }

    public function getFilename() : string
    {
        return $this->filename;
    }

    public function setPath(string $path)
    {
        $this->path = $path."/";
    }

    public function getContents() : string
    {
        $ret = @file_get_contents($this->getAbsoluteFilename());
        if ($ret === FALSE) throw new Exception("Unable to read file: $this->path.$this->filename");
        return $ret;
    }

    public function getAbsoluteFilename() : string
    {
        return $this->path.$this->filename;
    }

    public function getMIME() : string
    {
        return @$this->finfo->file($this->getAbsoluteFilename());
    }

    public function getBase64(): string
    {
        return base64_encode($this->getContents());
    }

    public function exists() : bool
    {
        return file_exists($this->getAbsoluteFilename());
    }

    public function getLength() : int
    {
        return filesize($this->getAbsoluteFilename());
    }

    public function open(string $mode)
    {
        $resource = @fopen($this->getAbsoluteFilename(), $mode);
        if ($resource === FALSE) {
            throw new Exception("Unable to open file for this mode");
        }
        $this->handle = $resource;
    }

    public function close()
    {
        @fclose($this->handle);
    }

    public function write(string $buffer)
    {
        $result = @fwrite($this->handle, $buffer);
        if ($result === FALSE) {
            throw new Exception("Unable to write to file");
        }
    }

    public function read(int $length = 0) : ?string
    {
        $res = fread($this->handle, $length);
        if ($res === FALSE) {
            throw new Exception("Unable to read from file");
        }
        return $res;
    }

    public function passthrough()
    {
        if (!$this->handle) throw new Exception("Empty handle for file: ".$this->getAbsoluteFilename());
        fpassthru($this->handle);
    }

    public function length() : int
    {
        $result = filesize($this->getAbsoluteFilename());
        if ($result === FALSE) throw new Exception("Unable to get filesize");
        return $result;
    }

    /**
     * Pass-through the file as a HTML response using additional headers passed in the '$headers' parameter
     * where header names are the keys and values are the value.
     * Default headers are content-type content-length and content-disposition that can be overwritten with the passed headers
     * @param array $headers
     * @throws Exception
     */
    public function response(array $user_headers=array())
    {
        $headers = array();
        $headers["Content-Type"] = $this->getMIME();
        $headers["Content-Length"] = $this->length();
        $headers["Content-Disposition"] = "inline; filename='\"'{$this->getAbsoluteFilename()}\"";
        foreach ($user_headers as $key=>$val) {
            $headers[$key] = $val;
        }

        foreach ($headers as $key=>$val) {
            header($key.": ".$val);
        }
        $this->open("r");
        $this->passthrough();
        $this->close();

    }

    public function remove() : bool
    {
        return unlink($this->getAbsoluteFilename());
    }
}
?>
