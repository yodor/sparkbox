<?php
include_once("storage/SparkHTTPResponse.php");

class SparkFileHTTPResponse extends SparkHTTPResponse
{
    protected SparkFile $file;


    public function __construct()
    {
        parent::__construct();
    }

    public function setFile(SparkFile $file)
    {
        $this->file = $file;
    }

    public function getFile() : SparkFile
    {
        return $this->file;
    }

    public function setData(string $data, int $dataSize)
    {
        throw new Exception("setData unsupported");
    }

    public function getData() : string
    {
        throw new Exception("setData unsupported");
    }

    public function getSize() : int
    {
        return $this->file->length();
    }

    public function send(bool $doExit = TRUE)
    {

        debug("Headers: ".print_r($this->headers, true));

        //match cache data
        $lastModified = $this->file->lastModified();
        $this->checkCacheLastModifed($lastModified);

        $etag = sparkHash($this->file->getFilename()."-".$lastModified);
        $this->checkCacheETag($etag);

        $this->setHeader("Last-Modified", gmdate(SparkHTTPResponse::DATE_FORMAT, $lastModified));
        $this->setHeader("ETag", $etag);

        //send data if no cache match
        $this->sendFile($this->file);

        if ($doExit) exit;

    }

}
