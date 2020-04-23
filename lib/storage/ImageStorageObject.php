<?php
include_once("lib/storage/FileStorageObject.php");

class ImageStorageObject extends FileStorageObject
{

    protected $width = -1;
    protected $height = -1;

    public function __construct(FileStorageObject $file_storage = NULL)
    {
        parent::__construct();

        //copy FileStorageObject as ImageStorageObject
        if ($file_storage) {

            $this->setUploadStatus($file_storage->getUploadStatus());
            $this->setMIME($file_storage->getMIME());
            $this->setTimestamp($file_storage->getTimestamp());
            $this->setTempName($file_storage->getTempName());
            $this->setFilename($file_storage->getFilename());
            $this->setUID($file_storage->getUID());

            //call setData() last as we process tempName when getData() is zero for ajax uploaded files
            $this->setData($file_storage->getData());

            //$this->setPurged($file_storage->isPurged());
            debug("ImageStorageObject::CTOR: Created copy of FileStorageObject UID: " . $file_storage->getUID());

        }
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setWidth($width)
    {
        $this->width = $width;
    }

    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function setData($data)
    {
        debug("ImageStorageObject::setData() Query image dimensions: Data Length: " . strlen($data));

        $source = false;

        //data is empty for during upload to limit memory usage. Using temporary file name
        if (strlen($data) == 0) {

            if (strlen($this->getTempName()) == 0) {
                debug("ImageStorageObject::setData() Empty Data and TempName!");
                throw new Exception("Unable to create image object using the input data (TempName and data are null)");
            }

            debug("ImageStorageObject::setData() Data is empty. Trying uploaded file: " . $this->getTempName());

            $source = $this->imageFromTemp();

        }
        else {

            $source = @imagecreatefromstring($data);
            if (!$source) throw new Exception("Unable to create image object using the input data");

        }

        $this->width = imagesx($source);
        $this->height = imagesy($source);

        @imagedestroy($source);

        debug("ImageStorageObject::setData() Dimensions WIDTH:{$this->width} HEIGHT:{$this->height}");

        if ($this->width < 1 || $this->height < 1) {
            throw new Exception("Invalid image dimensions from input data");
        }
        parent::setData($data);

    }

    //only valid during request lifetime, using is_uploaded_file
    public function imageFromTemp()
    {
        if (!is_uploaded_file($this->getTempName())) throw new Exception("Not an uploaded file: " . $this->getTempName());

        debug("ImageStorageObject::imageFromTemp() File: " . $this->getTempName() . " is valid uploaded file");

        debug("Memory Info - memory_limit: " . ini_get("memory_limit") . " | memory_usage: " . memory_get_usage());

        debug("ImageStorageObject::imageFromTemp() MIME: " . $this->getMIME());

        //check mimes
        $source = false;

        if (strcasecmp($this->getMIME(), "image/jpeg") == 0 || strcasecmp($this->getMIME(), "image/jpg") == 0) {
            $source = @imagecreatefromjpeg($this->getTempName());
        }
        else if (strcasecmp($this->getMIME(), "image/png") == 0) {
            $source = @imagecreatefrompng($this->getTempName());
        }
        else if (strcasecmp($this->getMIME(), "image/gif") == 0) {
            $source = @imagecreatefromgif($this->getTempName());
        }
        if (!$source) throw new Exception("Unable to create image from uploaded file. Temp name is: " . $this->getTempName());

        return $source;
    }

    public function deconstruct(array &$row, $data_key = "data", $doEscape = true)
    {
        parent::deconstruct($row, $data_key, $doEscape);

        $row["width"] = $this->width;
        $row["height"] = $this->height;
    }

}

?>
