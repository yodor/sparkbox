<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/ImageStorageObject.php");
include_once("utils/ImageScaler.php");

class ImageDataResponse extends BeanDataResponse
{

    const string KEY_WIDTH = "width";
    const string KEY_HEIGHT = "height";
    const string KEY_SIZE = "size";

    const string KEY_FILTER = "filter";

    const string FILTER_GRAY = "gray";

    protected string $field = BeanDataResponse::FIELD_PHOTO;

    protected ImageScaler $scaler;

    public function __construct(int $id, string $className)
    {
        $width = 0;
        $height = 0;

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
            if (str_contains($_GET[ImageDataResponse::KEY_FILTER],self::FILTER_GRAY)) {
                $this->scaler->setGrayFilterEnabled(true);
            }
        }
        //call last - cache entry needs cacheName
        parent::__construct($id, $className);
    }

    protected function process()
    {

        $buffer = $this->object->buffer();
        if ($buffer->length()<1) throw new Exception("Empty data");

        $mime = $buffer->mime();
        if (!str_contains($mime, "image")) throw new Exception("Not an image data: $mime");

        //disable watermark for this request if bean does not require it
        if (!$this->require_watermark) {
            $this->scaler->getWatermark()->disable();
        }
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
        if ($this->scaler->isGrayFilterEnabled()) {
            $parts[] = ImageDataResponse::FILTER_GRAY;
        }
        return implode("-", $parts);
    }

    protected function ETag() : string
    {
        $parts = array();

        if ($this->scaler->getWatermark()->isEnabled()) {

            $watermark = $this->scaler->getWatermark();
            $parts[] = $watermark->getMarginX();
            $parts[] = $watermark->getMarginY();
            $parts[] = $watermark->getPosition();

            $file = $watermark->getFile();
            $parts[] = $file->getAbsoluteFilename();
            $parts[] = $file->length();
            $parts[] = $file->getMIME();
            $parts[] = $file->lastModified();

        }

        $parts[] = $this->cacheName();
        $parts[] = $this->getLastModified();

        return sparkHash(implode("-", $parts));
    }

}
