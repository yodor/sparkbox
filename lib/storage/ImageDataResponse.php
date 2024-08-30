<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/ImageStorageObject.php");
include_once("utils/ImageScaler.php");

class ImageDataResponse extends BeanDataResponse
{

    const KEY_WIDTH = "width";
    const KEY_HEIGHT = "height";
    const KEY_SIZE = "size";

    protected string $field = BeanDataResponse::FIELD_PHOTO;

    protected ImageScaler $scaler;

    public function __construct(int $id, string $className)
    {
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

        //call last - cache entry needs cacheName
        parent::__construct($id, $className);
    }

    protected function process()
    {

        if (isset($this->row["watermark_enabled"]) && ($this->row["watermark_enabled"]>0)) {
            debug("Object requires watermark");

            if ($this->scaler->isWatermarkEnabled()) {
                debug("Scaler watermark is enabled and can be used");
                $this->scaler->watermark_required = true;
            }
            else {
                debug("Scaler watermark is unavailable");
                $this->scaler->watermark_required = false;
            }
        }

        $buffer = $this->object->buffer();
        if ($buffer->length()<1) throw new Exception("Empty data");

        $mime = $buffer->mime();
        if (!str_contains($mime, "image")) throw new Exception("Not an image data: $mime");

        //place result in buffer
        $this->scaler->process($buffer);

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
