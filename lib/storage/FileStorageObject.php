<?php
include_once("storage/StorageObject.php");

class FileStorageObject extends StorageObject
{

    protected $dataKey = "data";

    protected $mime = "application/octet-stream";
    protected $filename = NULL;
    protected $temp_name = NULL;


    public function __construct()
    {
        parent::__construct();
    }

    public function setFilename($name)
    {
        $this->filename = $name;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getMIME()
    {
        return $this->mime;
    }

    public function setMIME($mime)
    {
        $this->mime = $mime;
    }

    public function setTempName($temp_name)
    {
        $this->temp_name = $temp_name;
    }

    public function getTempName()
    {
        return $this->temp_name;
    }

    public function deconstruct(array &$row, $doEscape = true)
    {
        parent::deconstruct($row, $doEscape);

        $row["mime"] = $this->mime;
        $row["filename"] = $this->filename;
        $row["temp_name"] = $this->temp_name;
        $row["upload_status"] = $this->upload_status;

    }
}

?>
