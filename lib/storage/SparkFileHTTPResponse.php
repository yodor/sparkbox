<?php
include_once("storage/SparkHTTPResponse.php");

class SparkFileHTTPResponse extends SparkHTTPResponse
{
    protected SparkFile $file;

    public function __construct(SparkFile $file = null)
    {
        parent::__construct();

        $this->setHeader("Content-Transfer-Encoding", "binary");
        $this->setHeader("Cache-Control", "max-age=31556952, must-revalidate");

        $expires = gmdate(SparkHTTPResponse::DATE_FORMAT, strtotime("+1 year"));
        $this->setHeader("Expires", $expires);

        if (!is_null($file)) {
            $this->setFile($file);
        }
    }

    public function setFile(SparkFile $file)
    {
        $this->file = $file;

        $filename = basename($this->file->getAbsoluteFilename());
        $this->setHeader("Content-Disposition", "inline; filename='$filename'");
        $this->setHeader("Content-Type", $this->file->getMIME());
        $this->setHeader("Content-Length",  $this->file->length());

        $last_modified = gmdate(SparkHTTPResponse::DATE_FORMAT, $this->file->lastModified());
        $this->setHeader("Last-Modified", $last_modified);

        $etag = sparkHash($this->file->getAbsoluteFilename()."|".$last_modified);
        debug("ETag: $etag");

        $this->setHeader("ETag", $etag);
    }

    public function setData(string $data, int $dataSize)
    {
        throw new Exception("setData unsupported");
    }

    public function getData() : string
    {
        return $this->file->getContents();
    }

    public function getSize() : int
    {
        return $this->file->length();
    }

    public function send(bool $doExit = TRUE)
    {

        debug("Headers: ".print_r($this->headers, true));

        //match cache data
        $this->checkCacheLastModifed($this->file->lastModified());
        $this->checkCacheETag($this->getHeader("ETag"));

        //send data if no cache match
        $this->sendFile($this->file);

        if ($doExit) exit;

    }

}
