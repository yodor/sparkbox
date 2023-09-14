<?php
include_once("storage/FileStorageObject.php");

class ImageStorageObject extends FileStorageObject
{

    protected $dataKey = "photo";

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
            debug("Created copy of FileStorageObject UID: " . $file_storage->getUID());

        }
    }

    public function getWidth() : int
    {
        return $this->width;
    }

    public function getHeight() : int
    {
        return $this->height;
    }

    public function setWidth(int $width)
    {
        $this->width = $width;
    }

    public function setHeight(int $height)
    {
        $this->height = $height;
    }

    public function setData($data)
    {
        debug("Querying image dimensions. Data Length: " . strlen($data));

        $source = FALSE;

        //data is empty for during upload to limit memory usage. Using temporary file name
        if (strlen($data) == 0) {
            debug("Data length = 0");

            if (strlen($this->getTempName()) == 0) {
                debug("Error - TempName is empty");
                throw new Exception("Unable to create image object using the input data (TempName and data are null)");
            }

            debug("Using data of uploaded file: " . $this->getTempName());

            $source = $this->imageFromTemp();

        }
        else {

            $source = @imagecreatefromstring($data);
            if (!$source) throw new Exception("Unable to create image object using the input data");

        }

        $this->width = imagesx($source);
        $this->height = imagesy($source);

        @imagedestroy($source);

        debug("Image dimensions [{$this->width} x {$this->height}]");

        if ($this->width < 1 || $this->height < 1) {
            throw new Exception("Invalid image dimensions from input data");
        }
        parent::setData($data);

    }

    //only valid during request lifetime, using is_uploaded_file
    public function imageFromTemp()
    {
        if (!is_uploaded_file($this->getTempName())) {
            debug("File: " . $this->getTempName() . " is not a valid uploaded file");
            throw new Exception("Not an uploaded file: " . $this->getTempName());
        }

        debug("File: " . $this->getTempName() . " is valid uploaded file. MIME: " . $this->getMIME());

        debug("Memory Info - memory_limit: " . ini_get("memory_limit") . " | memory_usage: " . memory_get_usage());

        //check mimes
        $source = FALSE;

        if (strcasecmp($this->getMIME(), "image/jpeg") == 0 || strcasecmp($this->getMIME(), "image/jpg") == 0) {
            $source = @imagecreatefromjpeg($this->getTempName());
        }
        else if (strcasecmp($this->getMIME(), "image/png") == 0) {
            $source = @imagecreatefrompng($this->getTempName());
        }
        else if (strcasecmp($this->getMIME(), "image/gif") == 0) {
            $source = @imagecreatefromgif($this->getTempName());
        }

        if (!$source) {
            debug("Unable to create image object from this file: " . $this->getTempName());
            throw new Exception("Unable to create image from uploaded file. Temp name is: " . $this->getTempName());
        }

        debug("Image created successfully from file: " . $this->getTempName());
        return $source;
    }

    public function deconstruct(array &$row, $doEscape = TRUE)
    {
        parent::deconstruct($row, $doEscape);

        $row["width"] = $this->width;
        $row["height"] = $this->height;
    }

}

?>