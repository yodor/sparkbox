<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/ImageStorageObject.php");
include_once("utils/ImageScaler.php");

class ImageDataResponse extends BeanDataResponse
{

    const KEY_WIDTH = "width";
    const KEY_HEIGHT = "height";
    const KEY_SIZE = "size";

    protected string $field = "photo";

    protected ImageScaler $scaler;

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

    }

    protected function processData()
    {

        if (isset($this->row["watermark_enabled"]) && ($this->row["watermark_enabled"]>0)) {
            debug("Object requires watermark");

            if ($this->scaler->isWatermarkEnabled()) {
                debug("Scaler watermark is enabled and can be used");
                $this->scaler->watermark_required = true;
                //$this->etag_parts[] = "watermark|".$this->scaler->getWatermarkPosition();
            }
            else {
                debug("Scaler watermark is unavailable");
                $this->scaler->watermark_required = false;
            }
        }
        $this->scaler->process($this->row[$this->field], $this->row["size"], $this->row["mime"]);
        $this->setData($this->scaler->getData(), $this->scaler->getDataSize());
    }

    protected function cacheName() : string
    {
        $parts = array();
        $parts[] = $this->field;
        $parts[] = $this->scaler->getWidth();
        $parts[] = $this->scaler->getHeight();
        $parts[] = $this->scaler->getMode();
        return implode("-", $parts);
    }


}
