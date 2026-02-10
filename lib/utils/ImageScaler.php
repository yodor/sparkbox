<?php
include_once("storage/DataBuffer.php");
include_once("storage/SparkWatermark.php");
include_once("utils/ImageType.php");

class ImageScaler
{

    const array SupportedMimes  = array(ImageType::TYPE_JPG->value, ImageType::TYPE_JPEG->value, ImageType::TYPE_PNG->value, ImageType::TYPE_WEBP->value);


    /**
     * Default Output Format
     * @var ImageType
     */
    protected ImageType $output_format = ImageType::TYPE_WEBP;

    protected int $output_quality = 60;

    protected ScaleMode $mode = ScaleMode::MODE_FULL;

    protected int $width = 0;
    protected int $height = 0;

    protected bool $grayFilterEnabled = false;

    protected SparkWatermark $watermark;

    //resulting image width and height
    public function __construct(int $width, int $height)
    {
        if ($width<1)$width = 0;
        if ($height<1)$height = 0;

        $this->width = $width;
        $this->height = $height;

        if ($this->width > 0 || $this->height > 0) {
            $this->mode = ScaleMode::MODE_CROP;

            if ($this->width == $this->height) {
                $this->mode = ScaleMode::MODE_THUMB;
            }
        }
        $this->setOutputQuality(Spark::Get(Config::IMAGE_SCALER_OUTPUT_QUALITY));
        $this->setOutputFormat(Spark::Get(Config::IMAGE_SCALER_OUTPUT_FORMAT));

        $this->watermark = new SparkWatermark();

    }

    public function setGrayFilterEnabled(bool $mode) : void
    {
        $this->grayFilterEnabled = $mode;
    }

    public function isGrayFilterEnabled() : bool
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
    public function setOutputQuality(int $output_quality) : void
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
    public function setOutputFormat(string $mime) : void
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

    public function getMode(): ScaleMode
    {
        return $this->mode;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function process(DataBuffer $buffer) : void
    {

        if ($this->mode === ScaleMode::MODE_CROP) {
            Debug::ErrorLog("Using CROP mode");
            $this->processCrop($buffer);
        } else if ($this->mode === ScaleMode::MODE_THUMB) {
            Debug::ErrorLog("Using THUMB mode");
            $this->processThumb($buffer);
        } else {
            //as is
            Debug::ErrorLog("Using as-is mode");
            $this->processFull($buffer);
        }
    }
    protected function imageFromBuffer(DataBuffer $buffer) : GdImage
    {
        $h_source = @imagecreatefromstring($buffer->data());
        if ($h_source === FALSE) {
            throw new Exception("Image can not be created from this input data");
        }
        return $h_source;
    }

    /**
     * Return the full image without scaling
     * If watermark is enabled it is applied otherways does nothing with the buffer
     *
     * @param DataBuffer $buffer Source/Destination of the image data
     * @return void
     * @throws Exception
     */
    protected function processFull(DataBuffer $buffer) : void
    {
        if ($this->watermark->isEnabled()) {
            $h_source = $this->imageFromBuffer($buffer);
            $this->setImageData($buffer, $h_source);
            $h_source=null;
        }

    }

    /**
     * Create resized version (CROP to square) of the image found in the DataBuffer
     *
     * @param DataBuffer $buffer Source/Destination of the image data
     * @return void
     * @throws Exception
     */
    protected function processCrop(DataBuffer $buffer) : void
    {
        $h_source = $this->imageFromBuffer($buffer);

        $image_width = imagesx($h_source);
        $image_height = imagesy($h_source);

        //1264x720
        //449x256
        $ratio = 0;

        if ($this->height > 0) {
            //scale to height , width auto
            $ratio = $image_height / $this->height;
            $this->width = (int)($image_width / $ratio);

            Debug::ErrorLog("Using fit to height");
        } else if ($this->width > 0) {
            $ratio = $image_width / $this->width;
            $this->height = (int)($image_height / $ratio);

            Debug::ErrorLog("Using fit to width");
        }


        Debug::ErrorLog("Output size: [$this->width, $this->height]");
        $h_crop = $this->createImage($this->width, $this->height);

        // Resize
        imagecopyresampled($h_crop, $h_source, 0, 0, 0, 0, $this->width, $this->height, $image_width, $image_height);
        $h_source = null;

        $this->setImageData($buffer, $h_crop);

        $h_crop = null;

    }

    /**
     * Create resized version (SCALE-CROP to square) of the image found in the DataBuffer
     *
     * @param DataBuffer $buffer Source/Destination of the image data
     * @return void
     * @throws Exception
     */
    protected function processThumb(DataBuffer $buffer) : void
    {

        $h_source = $this->imageFromBuffer($buffer);

        $image_width = imagesx($h_source);
        $image_height = imagesy($h_source);
        $image_size = min($image_width, $image_height);

        $h_crop = imagecrop($h_source, ['x' => ($image_width - $image_size) / 2,
            'y' => ($image_height - $image_size) / 2, 'width' => $image_size,
            'height' => $image_size]);
        $h_source = null;

        $h_thumbnail = $this->createImage($this->width, $this->width);

        imagecopyresampled($h_thumbnail, $h_crop, 0, 0, 0, 0, $this->width, $this->width, $image_size, $image_size);
        $h_crop = null;

        $this->setImageData($buffer, $h_thumbnail);

        $h_thumbnail = null;
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
    protected function setImageData(DataBuffer $buffer, GdImage $h_source) : void
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
        } else if ($this->output_format === ImageType::TYPE_JPEG) {
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
    protected function createImage(int $width, int $height) : GdImage
    {
        $h_thumbnail = imagecreatetruecolor($width, $height);
        imageantialias($h_thumbnail, TRUE);
        imagealphablending($h_thumbnail, TRUE);
        return $h_thumbnail;
    }

}
