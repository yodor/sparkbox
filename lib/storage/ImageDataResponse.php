<?php
include_once ("storage/BeanDataResponse.php");
include_once ("storage/ImageStorageObject.php");
include_once ("utils/ImageScaler.php");

class ImageDataResponse extends BeanDataResponse
{

    protected $disposition  = "inline";
    protected $field = "photo";

    protected $scaler = NULL;

    public function __construct(int $id, string $className)
    {
        parent::__construct($id, $className);

        $width = -1;
        $height = -1;

        if (isset($_GET["width"])) {
            $width = (int)$_GET["width"];
        }

        if (isset($_GET["height"])) {
            $height = (int)$_GET["height"];
        }

        if (isset($_GET["size"])) {
            $size = (int)$_GET["size"];
            $width = $size;
            $height = $size;
        }

        $this->scaler = new ImageScaler($width, $height);

        if (isset($_GET["gray_filter"])) {
            $this->scaler->grayFilter = true;

        }

        $this->etag_parts[] = $this->scaler->getWidth();
        $this->etag_parts[] = $this->scaler->getHeight();
        $this->etag_parts[] = $this->scaler->getMode();

        $this->etag_parts[] = $this->scaler->grayFilter;

        debug("Using field: {$this->field}");

    }

    protected function processData()
    {
        $this->scaler->process($this->row[$this->field], $this->row["mime"]);
        $this->setData($this->scaler->getData(), $this->scaler->getDataSize());
    }

}