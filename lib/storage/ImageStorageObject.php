<?php
include_once("storage/FileStorageObject.php");

class ImageStorageObject extends FileStorageObject
{

    protected int $width = -1;
    protected int $height = -1;

    public function __construct(FileStorageObject $file_storage = NULL)
    {
        parent::__construct();
        $this->dataKey = "photo";

        //copy
        if ($file_storage) {

            $this->setTimestamp($file_storage->timestamp());
            $this->setFilename($file_storage->getFilename());
            $this->setUID($file_storage->UID());
            $this->setData($file_storage->data());

            debug("Copied data from FileStorageObject UID: " . $file_storage->UID());
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

    protected function processData(string $data) : void
    {
        debug("Querying image dimensions. Data Length: " . strlen($data));

        $source = @imagecreatefromstring($data);
        if (!$source) throw new Exception("Unable to create image object using the input data");

        $this->width = imagesx($source);
        $this->height = imagesy($source);

        @imagedestroy($source);

        debug("Image dimensions [{$this->width} x {$this->height}]");

        if ($this->width < 1 || $this->height < 1) {
            throw new Exception("Invalid image dimensions from input data");
        }
    }
    public function setData(string $data) : void
    {
        $this->processData($data);
        parent::setData($data);
    }

    public function deconstruct(array &$row, $doEscape = TRUE) : void
    {
        parent::deconstruct($row, $doEscape);

        $row["width"] = $this->width;
        $row["height"] = $this->height;
    }

    public function __serialize(): array
    {
        $result = parent::__serialize();
        $result["width"] = $this->width;
        $result["height"] = $this->height;
        return $result;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->width = (int)$data[$this->keyName("width")];
        $this->height = (int)$data[$this->keyName("height")];
    }
}

?>
