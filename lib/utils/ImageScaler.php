<?php
include_once("storage/DataBuffer.php");
include_once("storage/SparkWatermark.php");
include_once("utils/ImageType.php");

/**
 * Scales an image to bounds, keeping its proportions: each positive number is an upper limit - width 640: at most
 * 640 wide; height 640: at most 640 high; both: fits inside the box; none: the image as it is. Nothing is cut; an
 * image smaller than the bounds is not enlarged (unless setUpscaleEnabled - Config::IMAGE_SCALER_UPSCALE).
 * The output: the configured format and quality, the watermark and the gray filter when enabled.
 */
class ImageScaler
{

    const array SupportedMimes = array(ImageType::TYPE_JPG->value, ImageType::TYPE_JPEG->value, ImageType::TYPE_PNG->value, ImageType::TYPE_WEBP->value);

    /**
     * Default Output Format
     * @var ImageType
     */
    protected ImageType $output_format = ImageType::TYPE_WEBP;

    protected int $output_quality = 60;

    //the bounds - 0: no limit on that side
    protected int $width = 0;
    protected int $height = 0;

    protected bool $upscaleEnabled = false;

    protected bool $grayFilterEnabled = false;

    protected SparkWatermark $watermark;

    /**
     * @param int $width the most width (0 or less: no limit)
     * @param int $height the most height (0 or less: no limit)
     */
    public function __construct(int $width, int $height)
    {
        $this->width = max(0, $width);
        $this->height = max(0, $height);

        $this->setOutputQuality(Spark::Get(Config::IMAGE_SCALER_OUTPUT_QUALITY));
        $this->setOutputFormat(Spark::Get(Config::IMAGE_SCALER_OUTPUT_FORMAT));
        $this->upscaleEnabled = Spark::GetBoolean(Config::IMAGE_SCALER_UPSCALE);

        $this->watermark = new SparkWatermark();
    }

    /**
     * Enlarge an image smaller than the bounds (default Config::IMAGE_SCALER_UPSCALE)
     */
    public function setUpscaleEnabled(bool $mode) : void
    {
        $this->upscaleEnabled = $mode;
    }

    public function isUpscaleEnabled() : bool
    {
        return $this->upscaleEnabled;
    }

    public function setGrayFilterEnabled(bool $mode): void
    {
        $this->grayFilterEnabled = $mode;
    }

    public function isGrayFilterEnabled(): bool
    {
        return $this->grayFilterEnabled;
    }

    public function getWatermark(): SparkWatermark
    {
        return $this->watermark;
    }

    /**
     * Set output compression quality percentage
     * @param int $output_quality
     * @return void
     */
    public function setOutputQuality(int $output_quality): void
    {
        $this->output_quality = $output_quality;
    }

    public function getOutputQuality(): int
    {
        return $this->output_quality;
    }

    /**
     * Set output format mime type. Throws exception if the required format is not supported
     * ImageScaler->$supported_mimes holds the output formats
     * @param string $mime
     * @return void
     * @throws Exception
     */
    public function setOutputFormat(string $mime): void
    {
        $format = ImageType::tryFrom($mime);

        if ($format === null) throw new Exception("Unsupported output format type");

        $this->output_format = $format;
    }

    /**
     * @return ImageType Return the output format
     */
    public function getOutputFormat(): ImageType
    {
        return $this->output_format;
    }

    /**
     * The width bound - 0: no limit
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * The height bound - 0: no limit
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * The size of an image of '$imageWidth' x '$imageHeight' inside the bounds - proportions kept, at least 1x1
     * @return array{int, int}
     */
    public function targetSize(int $imageWidth, int $imageHeight) : array
    {
        $ratio = INF;
        if ($this->width > 0) $ratio = min($ratio, $this->width / $imageWidth);
        if ($this->height > 0) $ratio = min($ratio, $this->height / $imageHeight);
        if ($ratio === INF) return array($imageWidth, $imageHeight);
        if (!$this->upscaleEnabled) $ratio = min($ratio, 1.0);
        return array(max(1, (int)round($imageWidth * $ratio)), max(1, (int)round($imageHeight * $ratio)));
    }

    /**
     * The image of the buffer inside the bounds - the buffer gets the result
     * @param DataBuffer $buffer Source/Destination of the image data
     * @return void
     * @throws Exception
     */
    public function process(DataBuffer $buffer): void
    {
        //no bounds, no filter, no watermark: the image as it is
        if ($this->width < 1 && $this->height < 1 && !$this->watermark->isEnabled() && !$this->grayFilterEnabled) {
            Debug::ErrorLog("Image as it is");
            return;
        }

        $h_source = $this->imageFromBuffer($buffer);
        $image_width = imagesx($h_source);
        $image_height = imagesy($h_source);

        [$width, $height] = $this->targetSize($image_width, $image_height);
        Debug::ErrorLog("Image [$image_width x $image_height] bounds [$this->width x $this->height] -> [$width x $height]");

        if ($width === $image_width && $height === $image_height) {
            $this->setImageData($buffer, $h_source);
            return;
        }

        $h_scaled = $this->createImage($width, $height);
        imagecopyresampled($h_scaled, $h_source, 0, 0, 0, 0, $width, $height, $image_width, $image_height);
        $h_source = null;
        $this->setImageData($buffer, $h_scaled);
    }

    protected function imageFromBuffer(DataBuffer $buffer): GdImage
    {
        $h_source = @imagecreatefromstring($buffer->data());
        if ($h_source === FALSE) {
            throw new Exception("Image can not be created from this input data");
        }
        return $h_source;
    }

    /**
     * Put the image into the databuffer using the configured output format
     * Default output format is image/webp but is overriden from config define IMAGE_SCALER_OUTPUT_FORMAT
     * If watermark is enabled it is applied here
     * @param DataBuffer $buffer
     * @param GdImage $h_source
     * @return void
     * @throws Exception
     */
    protected function setImageData(DataBuffer $buffer, GdImage $h_source): void
    {
        Debug::ErrorLog("Set image data ...");

        if ($this->grayFilterEnabled) {
            imagefilter($h_source, IMG_FILTER_GRAYSCALE);
        }

        if ($this->watermark->isEnabled()) {
            $this->watermark->applyTo($h_source);
        }

        ob_start();
        if ($this->output_format === ImageType::TYPE_PNG) {
            imagesavealpha($h_source, TRUE);
            imagepng($h_source);
        }
        //"image/jpg" is a common alias - encode as JPEG, not the WebP fallback
        else if ($this->output_format === ImageType::TYPE_JPEG || $this->output_format === ImageType::TYPE_JPG) {
            imagejpeg($h_source, NULL, $this->output_quality);
        } else {
            imagewebp($h_source, NULL, $this->output_quality);
        }

        $buffer->setData(ob_get_contents());

        ob_end_clean();
    }

    /**
     * Create new empty image with given width and height
     * @param int $width
     * @param int $height
     * @return GdImage
     */
    protected function createImage(int $width, int $height): GdImage
    {
        $h_thumbnail = imagecreatetruecolor($width, $height);
        imageantialias($h_thumbnail, TRUE);
        imagealphablending($h_thumbnail, TRUE);
        return $h_thumbnail;
    }

}
