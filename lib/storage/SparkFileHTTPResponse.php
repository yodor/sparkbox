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

    public function getData()
    {
        return $this->file->getContents();
    }

    public function getSize()
    {
        return $this->file->length();
    }

    public function send(bool $doExit = TRUE)
    {

        debug("Headers: ".print_r($this->headers, true));

        $modifiedSince = strtotime($this->requestModifiedSince());
        $lastModified = $this->file->lastModified();

        debug("Request IF_MODIFIED_SINCE: ".$modifiedSince);
        debug("File last_modified: ".$lastModified);
        if ($modifiedSince !== FALSE) {
            if ($lastModified<=$modifiedSince) {
                debug("Request IF_MODIFIED_SINCE matching - responding with HTTP/304");
                $this->sendNotModified();
                exit;
            }
        }

        //browser is sending ETag
        $requestETag = $this->requestETag();
        debug("Request ETag is: ".$requestETag);
        debug("File ETag is: ". $this->getHeader("ETag"));
        if (strcasecmp($requestETag, $this->getHeader("ETag"))==0) {
            debug("Request ETag match file ETag - responding with HTTP/304");
            $this->sendNotModified();
            exit;
        }

        $this->sendFile($this->file->getAbsoluteFilename());

        if ($doExit) exit;

    }

}
