<?php
include_once("storage/DataBuffer.php");
include_once("storage/SparkWatermark.php");

class ImageScaler
{

    const int MODE_FULL = 0;
    const int MODE_CROP = 1;
    const int MODE_THUMB = 2;

    const string TYPE_JPG = "image/jpg";
    const string TYPE_JPEG = "image/jpeg";
    const string TYPE_PNG = "image/png";
    const string TYPE_WEBP = "image/webp";

    const array SupportedMimes  = array(ImageScaler::TYPE_JPG, ImageScaler::TYPE_JPEG, ImageScaler::TYPE_PNG, ImageScaler::TYPE_WEBP);


    /**
     * @var string
     */
    protected string $output_format = ImageScaler::TYPE_WEBP;
    protected int $output_quality = 60;

    protected int $mode = ImageScaler::MODE_FULL;

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
            $this->mode = ImageScaler::MODE_CROP;

            if ($this->width == $this->height) {
                $this->mode = ImageScaler::MODE_THUMB;
            }
        }
        $this->output_format = IMAGE_SCALER_OUTPUT_FORMAT;
        $this->output_quality = IMAGE_SCALER_OUTPUT_QUALITY;

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
        if (!in_array($mime, ImageScaler::SupportedMimes)) throw new Exception("Unsupported output format type");

        $this->output_format = $mime;
    }

    /**
     * @return string Return the output format mime type string
     */
    public function getOutputFormat(): string
    {
        return $this->output_format;
    }

    public function getMode(): int
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

        if ($this->mode == ImageScaler::MODE_CROP) {
            debug("Using CROP mode");
            $this->processCrop($buffer);
        } else if ($this->mode == ImageScaler::MODE_THUMB) {
            debug("Using THUMB mode");
            $this->processThumb($buffer);
        } else {
            //as is
            debug("Using as-is mode");
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
            imagedestroy($h_source);
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

            debug("Using fit to height");
        } else if ($this->width > 0) {
            $ratio = $image_width / $this->width;
            $this->height = (int)($image_height / $ratio);

            debug("Using fit to width");
        }


        debug("Output size: [$this->width, $this->height]");
        $h_crop = $this->createImage($this->width, $this->height);

        // Resize
        imagecopyresampled($h_crop, $h_source, 0, 0, 0, 0, $this->width, $this->height, $image_width, $image_height);
        imagedestroy($h_source);

        $this->setImageData($buffer, $h_crop);

        imagedestroy($h_crop);

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
        imagedestroy($h_source);

        $h_thumbnail = $this->createImage($this->width, $this->width);

        imagecopyresampled($h_thumbnail, $h_crop, 0, 0, 0, 0, $this->width, $this->width, $image_size, $image_size);
        imagedestroy($h_crop);

        $this->setImageData($buffer, $h_thumbnail);

        imagedestroy($h_thumbnail);
    }


    /**
     * Put the image into the databuffer using the configured output format
     * Default output format is image/webp but is overriden from config define IMAGE_SCALER_OUTPUT_FORMAT
     * If watermark is enabled it is applied here
     * @param DataBuffer $buffer Destination
     * @param GdImage $h_source Source image to be put into the buffer
     * @return void
     */
    protected function setImageData(DataBuffer $buffer, GdImage $h_source) : void
    {
        debug("Set image data ...");

        if ($this->grayFilterEnabled) {
            imagefilter($h_source, IMG_FILTER_GRAYSCALE);
        }

        if ($this->watermark->isEnabled()) {
            $this->watermark->applyTo($h_source);
        }

        ob_start();
        if (strcmp($this->output_format, ImageScaler::TYPE_PNG) == 0) {
            imagesavealpha($h_source, TRUE);
            imagepng($h_source);
        } else if (strcmp($this->output_format, ImageScaler::TYPE_JPEG) == 0) {
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
