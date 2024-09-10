<?php
class SparkFile {

    protected string $filename = "";
    protected string $path = "";
    protected finfo $finfo;
    protected $handle = NULL;

    /**
     * @param string $absolute_filename If not empty calls setAbsoluteFilename
     * @throws Exception
     */
    public function __construct(string $absolute_filename="")
    {
        $this->finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($absolute_filename) {
            $this->setAbsoluteFilename($absolute_filename);
        }
    }

    public function getETag() : string
    {
        return sparkHash($this->getFilename()."-".$this->lastModified());
    }

    /**
     * Set this file name to basename($filename)
     * @param string $filename set the filename to '$filename'
     */
    public function setFilename(string $filename) : void
    {
        $this->close();
        $this->filename = basename($filename);
    }

    /**
     * @return string
     */
    public function getFilename() : string
    {
        return $this->filename;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath(string $path) : void
    {
        $this->close();
        $this->path = $path."/";
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getContents() : string
    {
        if (!$this->exists()) throw new Exception("File not found");
        $ret = @file_get_contents($this->getAbsoluteFilename());
        if ($ret === FALSE) throw new Exception("Unable to read file: ".error_get_last()["message"]);
        return $ret;
    }

    /**
     * @return string The absolute filename (path + filename)
     */
    public function getAbsoluteFilename() : string
    {
        return $this->path.$this->filename;
    }

    /**
     * @param string $absolute_file The absolute filename (path + filename)
     * @throws Exception
     */
    public function setAbsoluteFilename(string $absolute_file) : void
    {
        $this->close();
        $path_parts = pathinfo($absolute_file);
        $this->setFilename($path_parts["basename"]);

        $path = $path_parts["dirname"];
        if (empty($path))throw new Exception("Path empty");
        $this->setPath($path);
    }

    /**
     * @return string
     */
    public function getMIME() : string
    {
        return @$this->finfo->file($this->getAbsoluteFilename());
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getBase64(): string
    {
        return base64_encode($this->getContents());
    }

    /**
     * @return bool
     */
    public function exists() : bool
    {
        return file_exists($this->getAbsoluteFilename());
    }

    /**
     * @param string $mode
     * @return void
     * @throws Exception
     */
    public function open(string $mode) : void
    {
        $this->close();
        $resource = @fopen($this->getAbsoluteFilename(), $mode);
        if (!is_resource($resource)) {
            throw new Exception("Unable to open file: ".error_get_last()["message"]);
        }
        $this->handle = $resource;
    }

    /**
     * @return bool
     */
    public function close() : bool
    {
        $result = false;
        if (is_resource($this->handle)) {
            $result = @fclose($this->handle);
        }
        return $result;
    }

    /**
     * @param string $buffer
     * @param int|null $length
     * @return int
     * @throws Exception
     */
    public function write(string $buffer, ?int $length = null) : int
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        $result = @fwrite($this->handle, $buffer, $length);
        if ($result === FALSE) {
            throw new Exception("Unable to write to file: ".error_get_last()["message"]);
        }
        return $result;
    }

    /**
     * @param int|null $length
     * @return string
     * @throws Exception
     */
    public function read(?int $length = null) : string
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        $res = @fread($this->handle, $length);
        if ($res === FALSE) {
            throw new Exception("Unable to read from file: ".error_get_last()["message"]);
        }
        return $res;
    }

    /**
     * @param int $operation
     * @param int|null $would_block
     * @return bool
     * @throws Exception
     */
    public function lock(int $operation, int &$would_block = null) : bool
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        return flock($this->handle, $operation, $would_block);
    }
    public function passthru() : void
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        fpassthru($this->handle);
    }

    public function length() : int
    {
        if (!$this->exists()) throw new Exception("File not found");
        $result = @filesize($this->getAbsoluteFilename());
        if ($result === FALSE) throw new Exception("Unable to get filesize");
        return $result;
    }

    /**
     * Remove the file from the filesystem
     * @return bool True on success False on failure
     * @throws Exception
     */
    public function remove() : bool
    {
        if (!$this->exists()) throw new Exception("File not found");
        return unlink($this->getAbsoluteFilename());
    }

    /**
     * Get last modified timestamp
     * @return int Unix timestamp of file last modified date
     * @throws Exception
     */
    public function lastModified() : int
    {
        if (!$this->exists()) throw new Exception("File not found");
        return filemtime($this->getAbsoluteFilename());
    }

    /**
     * Set last modified timestamp
     * @param int $lastModified  Unix timestamp of file last modified date
     * @return void
     * @throws Exception
     */
    public function setLastModified(int $lastModified) : void
    {
        if (!$this->exists()) throw new Exception("File not found");
        touch($this->getAbsoluteFilename(), $lastModified);
    }
}
?>
