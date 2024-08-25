<?php
class SparkFile {

    protected string $filename;
    protected string $path;
    protected finfo $finfo;
    protected $handle = NULL;

    /**
     * @param string $absolute_filename If not empty calls setAbsoluteFilename
     */
    public function __construct(string $absolute_filename="")
    {
        $this->finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($absolute_filename) {
            $this->setAbsoluteFilename($absolute_filename);
        }
    }

    /**
     * @param string $filename set the filename to '$filename'
     */
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

    public function getPath() : string
    {
        return $this->path;
    }

    public function getContents() : string
    {
        $ret = @file_get_contents($this->getAbsoluteFilename());
        if ($ret === FALSE) throw new Exception("Unable to read file: $this->path.$this->filename");
        return $ret;
    }

    /**
     * @return string The absolute filename path + filename
     */
    public function getAbsoluteFilename() : string
    {
        return $this->path.$this->filename;
    }

    /**
     * @param string $absolute_file The absolute filename path + filename
     */
    public function setAbsoluteFilename(string $absolute_file)
    {
        $path_parts = pathinfo($absolute_file);
        $this->filename = $path_parts["basename"];
        $this->path = $path_parts["dirname"];

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

    public function remove() : bool
    {
        return unlink($this->getAbsoluteFilename());
    }

    /**
     * @return int timestamp unix
     */
    public function lastModified() : int
    {
        return filemtime($this->getAbsoluteFilename());
    }
}
?>
