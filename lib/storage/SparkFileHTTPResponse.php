<?php
include_once("storage/HTTPResponse.php");

class SparkFileHTTPResponse extends HTTPResponse
{
    protected $file = null;

    public function __construct(SparkFile $file = null)
    {
        parent::__construct();
        $this->file = $file;
    }

    public function setFile(SparkFile $file)
    {
        $this->file = $file;
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
        $this->fillHeaders();

        debug("Headers: ".print_r($this->headers, true));

        //browser is sending ETag
        $requestETag = $this->requestETag();

        debug("Request ETag is: $requestETag");

        if (strcmp($requestETag, $this->getHeader("ETag"))==0) {
            $this->sendNotModified();
        }

        $this->sendFile($this->file->getAbsoluteFilename());

        if ($doExit) exit;

    }

    protected function fillHeaders()
    {

        //use timestamp from file
        $last_modified = gmdate(HTTPResponse::DATE_FORMAT, $this->file->dateCreated());
        debug("Using last-modified from [dateCreated]: ".$last_modified);

        //always keep one year ahead from request time
        $expires = gmdate(HTTPResponse::DATE_FORMAT, strtotime("+1 year", $this->file->dateCreated()));
        debug("expires: $expires");

        $etag = md5($last_modified);
        debug("ETag: $etag");

        $this->setHeader("Content-Type", $this->file->getMIME());
        $this->setHeader("Content-Length",  $this->file->length());

        $this->setHeader("ETag", $etag);
        $this->setHeader("Expires", $expires);
        $this->setHeader("Last-Modified", $last_modified);

        $this->setHeader("Cache-Control", "no-cache, must-revalidate");
        $this->setHeader("Content-Disposition", "inline; filename='{$this->file->getAbsoluteFilename()}'");
        $this->setHeader("Content-Transfer-Encoding", "binary");


    }
}