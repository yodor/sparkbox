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
            $this->setMIME($file_storage->getMIME());
            $this->setTimestamp($file_storage->getTimestamp());

            $this->setFilename($file_storage->getFilename());
            $this->setUID($file_storage->getUID());

            $this->setData($file_storage->getData());

            debug("Copied data from FileStorageObject UID: " . $file_storage->getUID());
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

    public function setWidth(int $width) : void
    {
        $this->width = $width;
    }

    public function setHeight(int $height) : void
    {
        $this->height = $height;
    }

    public function setData(string $data) : void
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
