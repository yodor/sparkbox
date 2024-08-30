<?php
include_once("storage/StorageObject.php");

class FileStorageObject extends StorageObject
{

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

    public function deconstruct(array &$row, $doEscape = TRUE) : void
    {
        parent::deconstruct($row, $doEscape);
        $row["filename"] = $this->filename;
    }

    public function __serialize(): array
    {
        $result = parent::__serialize();
        $result["filename"] = $this->filename;
        return $result;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->filename = (string)$data[$this->keyName("filename")];
    }
}

?>
