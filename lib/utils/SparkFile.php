<?php
class SparkFile {

    protected string $filename = "";
    protected string $path = "";
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
     * Set this file name to basename($filename)
     * @param string $filename set the filename to '$filename'
     */
    public function setFilename(string $filename)
    {
        $this->close();
        $this->filename = basename($filename);
    }

    public function getFilename() : string
    {
        return $this->filename;
    }

    public function setPath(string $path)
    {
        $this->close();
        $this->path = $path."/";
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getContents() : string
    {
        if (!$this->exists()) throw new Exception("File not found");
        $ret = @file_get_contents($this->getAbsoluteFilename());
        if ($ret === FALSE) throw new Exception("Unable to read file: ".error_get_last()["message"]);
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
        $this->close();
        $path_parts = pathinfo($absolute_file);
        $this->setFilename($path_parts["basename"]);

        $path = $path_parts["dirname"];
        if (empty($path))throw new Exception("Path empty");
        $this->setPath($path);
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

    public function open(string $mode)
    {
        $this->close();
        $resource = @fopen($this->getAbsoluteFilename(), $mode);
        if (!is_resource($resource)) {
            throw new Exception("Unable to open file: ".error_get_last()["message"]);
        }
        $this->handle = $resource;
    }

    public function close()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    public function write(string $buffer)
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        $result = @fwrite($this->handle, $buffer);
        if ($result === FALSE) {
            throw new Exception("Unable to write to file: ".error_get_last()["message"]);
        }
    }

    public function read(int $length = 0) : ?string
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        $res = @fread($this->handle, $length);
        if ($res === FALSE) {
            throw new Exception("Unable to read from file: ".error_get_last()["message"]);
        }
        return $res;
    }

    public function lock(int $operation, int &$would_block = null) : bool
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        return flock($this->handle, $operation, $would_block);
    }
    public function passthru()
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        fpassthru($this->handle);
    }

    public function length() : int
    {
        if (!$this->exists()) throw new Exception("File not found");
        $result = filesize($this->getAbsoluteFilename());
        if ($result === FALSE) throw new Exception("Unable to get filesize");
        return $result;
    }

    public function remove() : bool
    {
        if (!$this->exists()) throw new Exception("File not found");
        return unlink($this->getAbsoluteFilename());
    }

    /**
     * @return int timestamp unix
     */
    public function lastModified() : int
    {
        if (!$this->exists()) throw new Exception("File not found");
        return filemtime($this->getAbsoluteFilename());
    }
    public function setLastModified(int $lastModified) : void
    {
        if (!$this->exists()) throw new Exception("File not found");
        touch($this->getAbsoluteFilename(), $lastModified);
    }
}
?>
