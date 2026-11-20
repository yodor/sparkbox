<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/ImageStorageObject.php");
include_once("utils/ImageScaler.php");
include_once("utils/ImageSizes.php");

class ImageDataResponse extends BeanDataResponse
{

    const string KEY_WIDTH = "width";
    const string KEY_HEIGHT = "height";
    const string KEY_SIZE = "size";

    const string KEY_FILTER = "filter";

    const string FILTER_GRAY = "gray";

    protected ImageScaler $scaler;

    /**
     * The allowed size when the asked one is not enabled (ImageSizes) - answered with a 301 to its address
     * @var array<string, int>
     */
    protected array $allowedSize = array();

    public function __construct(int $id, string $className)
    {
        $width = max(0, (int)($_GET[ImageDataResponse::KEY_WIDTH] ?? 0));
        $height = max(0, (int)($_GET[ImageDataResponse::KEY_HEIGHT] ?? 0));

        //each number is a bound of the enabled sizes (ImageSizes); two numbers only as a square box - else the width
        $allowedWidth = ImageSizes::Allowed($width);
        $allowedHeight = ImageSizes::Allowed($height);
        if ($allowedWidth > 0 && $allowedHeight > 0 && $allowedWidth !== $allowedHeight) $allowedHeight = 0;
        if ($allowedWidth !== $width || $allowedHeight !== $height) {
            $this->allowedSize = array(ImageDataResponse::KEY_WIDTH => $allowedWidth, ImageDataResponse::KEY_HEIGHT => $allowedHeight);
        }

        $this->scaler = new ImageScaler($allowedWidth, $allowedHeight);

        if (isset($_GET[ImageDataResponse::KEY_FILTER])) {
            if (str_contains($_GET[ImageDataResponse::KEY_FILTER],self::FILTER_GRAY)) {
                $this->scaler->setGrayFilterEnabled(true);
            }
        }
        //call last - cache entry needs cacheName
        parent::__construct($id, $className, BeanDataResponse::FIELD_PHOTO);
    }

    /**
     * A size that is not enabled: 301 to the address of the allowed one (one address per size for the browsers
     * and the CDN, the cache holds only the enabled sizes)
     */
    public function send() : void
    {
        if (count($this->allowedSize) > 0 && $this->request) {
            $location = $this->request->parameterURL($this->allowedSize);
            if ($location !== "") {
                Debug::ErrorLog("Image size not enabled - redirecting to: $location");
                $this->sendRedirect($location, 301, SparkHTTPResponse::MAX_AGE_WEEK);
                exit;
            }
        }
        parent::send();
    }

    protected function process(): void
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

    /**
     * Slug URLs: .../<width>/<height>/... after cmd/class/id
     * @return array<string>
     */
    public static function RouteParameters() : array
    {
        return array(ImageDataResponse::KEY_WIDTH, ImageDataResponse::KEY_HEIGHT);
    }

    protected function isImage() : bool
    {
        return true;
    }

    protected function cacheName() : string
    {
        $parts = array();
        $parts[] = BeanDataResponse::FIELD_PHOTO;
        $parts[] = $this->scaler->getWidth();
        $parts[] = $this->scaler->getHeight();
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
            $parts[] = $watermark->getPosition()->value;

            $file = $watermark->getFile();
            $parts[] = $file->getAbsoluteFilename();
            $parts[] = $file->length();
            $parts[] = $file->getMIME();
            $parts[] = $file->lastModified();

        }

        $parts[] = $this->cacheName();
        $parts[] = $this->getLastModified();

        return Spark::Hash(implode("-", $parts));
    }

}