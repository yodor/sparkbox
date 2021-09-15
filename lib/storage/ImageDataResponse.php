<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/ImageStorageObject.php");
include_once("utils/ImageScaler.php");

class ImageDataResponse extends BeanDataResponse
{

    const KEY_WIDTH = "width";
    const KEY_HEIGHT = "height";
    const KEY_SIZE = "size";
    const KEY_FILTER = "filter";

    protected $disposition = "inline";
    protected $field = "photo";

    protected $scaler = NULL;

    public function __construct(int $id, string $className)
    {
        parent::__construct($id, $className);

        $width = -1;
        $height = -1;

        if (isset($_GET[ImageDataResponse::KEY_WIDTH])) {
            $width = (int)$_GET[ImageDataResponse::KEY_WIDTH];
        }

        if (isset($_GET[ImageDataResponse::KEY_HEIGHT])) {
            $height = (int)$_GET[ImageDataResponse::KEY_HEIGHT];
        }

        if (isset($_GET[ImageDataResponse::KEY_SIZE])) {
            $size = (int)$_GET[ImageDataResponse::KEY_SIZE];
            $width = $size;
            $height = $size;
        }

        $this->scaler = new ImageScaler($width, $height);

        if (isset($_GET[ImageDataResponse::KEY_FILTER])) {
            $this->scaler->grayFilter = TRUE;

        }

        $this->etag_parts[] = $this->scaler->getWidth();
        $this->etag_parts[] = $this->scaler->getHeight();
        $this->etag_parts[] = $this->scaler->getMode();
        $this->etag_parts[] = $this->scaler->getOutputFormat();

        $this->etag_parts[] = $this->scaler->grayFilter;

        debug("Using field: {$this->field}");

    }

    protected function processData()
    {
        $this->scaler->process($this->row[$this->field], $this->row["size"], $this->row["mime"]);
        $this->setData($this->scaler->getData(), $this->scaler->getDataSize());
    }

    protected function fillHeaders()
    {
        parent::fillHeaders();
        $this->setHeader("Content-Type", $this->scaler->getOutputFormat());
    }

}