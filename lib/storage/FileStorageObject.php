<?php
include_once("storage/StorageObject.php");

class FileStorageObject extends StorageObject
{

    protected string $mime = "application/octet-stream";
    protected string $filename = "";


    public function __construct()
    {
        parent::__construct();
        $this->dataKey = "data";
    }

    public function setFilename(string $name) : void
    {
        $this->filename = $name;
    }

    public function getFilename() : string
    {
        return $this->filename;
    }

    public function getMIME() : string
    {
        return $this->mime;
    }

    public function setMIME(string $mime) : void
    {
        $this->mime = $mime;
    }

    public function deconstruct(array &$row, $doEscape = TRUE) : void
    {
        parent::deconstruct($row, $doEscape);

        $row["mime"] = $this->mime;
        $row["filename"] = $this->filename;
    }

    public function __serialize(): array
    {
        $result = parent::__serialize();
        $result["mime"] = $this->mime;
        $result["filename"] = $this->filename;
        return $result;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->mime = (string)$data[$this->keyName("mime")];
        $this->filename = (string)$data[$this->keyName("filename")];
    }
}

?>
