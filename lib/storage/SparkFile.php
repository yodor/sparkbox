<?php
class SparkFile {

    protected string $filename = "";
    protected string $path = "";
    protected finfo $finfo;
    protected mixed $handle = NULL;

    /**
     * @param string $absolute_filename If not empty calls setAbsoluteFilename - must be an absolute path
     * @throws Exception If $absolute_filename is not empty and not absolute
     */
    public function __construct(string $absolute_filename="")
    {
        $this->finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($absolute_filename !== "") {
            $this->setAbsoluteFilename($absolute_filename);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Last error message helper - error_get_last() can be null
     * @param string $default
     * @return string
     */
    protected static function LastError(string $default) : string
    {
        return error_get_last()["message"] ?? $default;
    }

    public function getETag() : string
    {
        return Spark::Hash($this->getAbsoluteFilename()."-".$this->lastModified());
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
     * Set the folder of this file. The root folder "/" is kept as is.
     * @param string $path
     * @return void
     * @throws Exception If $path is empty - an empty config value must not resolve to the filesystem root
     */
    public function setPath(string $path) : void
    {
        $this->close();
        if (trim($path) === "") throw new Exception("Empty path");
        $this->path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
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
        if ($ret === FALSE) throw new Exception("Unable to read file: ".SparkFile::LastError($this->getAbsoluteFilename()));
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
     * Set path and filename from an absolute filename.
     * The value is not normalised ('..' is kept) - pass only paths built by code, never user input.
     * For user supplied names use setPath() + setFilename() (setFilename keeps only the basename).
     * @param string $absolute_file The absolute filename (path + filename)
     * @throws Exception If $absolute_file is not absolute
     */
    public function setAbsoluteFilename(string $absolute_file) : void
    {
        $this->close();

        if (!str_starts_with($absolute_file, "/") && !preg_match('/^[A-Za-z]:[\\\/]/', $absolute_file)) {
            throw new Exception("Not an absolute filename: '$absolute_file'");
        }

        $dir = pathinfo($absolute_file, PATHINFO_DIRNAME);
        $name = pathinfo($absolute_file, PATHINFO_BASENAME);

        $this->setPath($dir);
        $this->setFilename($name);
    }

    /**
     * MIME type detected from the file contents. 'application/octet-stream' if detection fails.
     * @return string
     */
    public function getMIME() : string
    {
        $mime = @$this->finfo->file($this->getAbsoluteFilename());
        return (is_string($mime) && $mime !== "") ? $mime : "application/octet-stream";
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
            throw new Exception("Unable to open file: ".SparkFile::LastError($this->getAbsoluteFilename()));
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
        $this->handle = NULL;
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
            throw new Exception("Unable to write to file: ".SparkFile::LastError($this->getAbsoluteFilename()));
        }
        return $result;
    }

    /**
     * @param int|null $length
     * @return string
     * @throws Exception
     */
    public function read(int $length = 8192) : false|string
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        return @fread($this->handle, $length);
    }

    /**
     * @param int $operation
     * @param int|null $would_block
     * @return bool
     * @throws Exception
     */
    public function lock(int $operation, ?int &$would_block = null) : bool
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        return flock($this->handle, $operation, $would_block);
    }

    public function seek(int $offset, int $whence = SEEK_SET) : int
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");

        $result = @fseek($this->handle, $offset, $whence);

        if ($result === -1) {
            throw new Exception("Seek to position failed: ".SparkFile::LastError($this->getAbsoluteFilename()));
        }
        return $result;
    }
    public function truncate(int $position) : bool
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        $result = @ftruncate($this->handle, $position);
        if ($result === false) {
            throw new Exception("Truncate to position failed: ".SparkFile::LastError($this->getAbsoluteFilename()));
        }
        return $result;
    }
    public function flush() : bool
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        return @fflush($this->handle);
    }

    public function passthru() : void
    {
        if (!is_resource($this->handle)) throw new Exception("Handle not a resource");
        fpassthru($this->handle);
    }

    /**
     * Single stat call - no exists() check before it, the file can vanish in between
     * @return int
     * @throws Exception If the file does not exist or can not be stat-ed
     */
    public function length() : int
    {
        $result = @filesize($this->getAbsoluteFilename());
        if ($result === FALSE) throw new Exception("File not found or not readable: ".$this->getAbsoluteFilename());
        return $result;
    }

    /**
     * Remove the file from the filesystem. Removing a missing file is not an error.
     * @return bool True if the file was removed, False if it did not exist or could not be removed
     */
    public function remove() : bool
    {
        return @unlink($this->getAbsoluteFilename());
    }

    /**
     * Get last modified timestamp - single stat call, the file can vanish between exists() and filemtime()
     * @return int Unix timestamp of file last modified date
     * @throws Exception If the file does not exist or can not be stat-ed
     */
    public function lastModified() : int
    {
        $result = @filemtime($this->getAbsoluteFilename());
        if ($result === FALSE) throw new Exception("File not found or not readable: ".$this->getAbsoluteFilename());
        return $result;
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